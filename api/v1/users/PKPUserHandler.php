<?php

/**
 * @file api/v1/users/PKPUserHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPUserHandler
 *
 * @ingroup api_v1_users
 *
 * @brief Base class to handle API requests for user operations.
 *
 */

namespace PKP\API\v1\users;

use APP\core\Application;
use APP\facades\Repo;
use Illuminate\Support\Str;

use Symfony\Component\HttpFoundation\StreamedResponse;


use Illuminate\Http\Response;

use InvalidArgumentException;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

use Exception;
use PKP\context\Context;
use PKP\core\APIResponse;
use PKP\core\PKPBaseController;
use PKP\facades\Locale;
use PKP\handler\APIHandler;
use PKP\plugins\Hook;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\UserRolesRequiredPolicy;
use PKP\security\Role;
use Slim\Http\Request as SlimRequest;
use Throwable;

class UserController extends PKPBaseController
{
    /**
     * Convert the query params passed to the end point. Exclude unsupported
     * params and coerce the type of those passed.
     *
     * @param array $params         Key/value of request params
     * @param array $allowedKeys    The param keys which should be processed and returned
     *
     * @return array
     */
    private function _processAllowedParams($params, $allowedKeys): array
    {
        // Merge query params over default params
        $defaultParams = [
            'count' => 20,
            'offset' => 0,
        ];

        $requestParams = array_merge($defaultParams, $params);

        // Process query params to format incoming data as needed
        $returnParams = [];
        foreach ($requestParams as $param => $val) {
            if (!in_array($param, $allowedKeys)) {
                continue;
            }
            switch ($param) {
                case 'orderBy':
                    if (in_array($val, ['id', 'familyName', 'givenName'])) {
                        $returnParams[$param] = $val;
                    }
                    break;

                case 'orderDirection':
                    $returnParams[$param] = $val === 'ASC' ? $val : 'DESC';
                    break;

                case 'status':
                    if (in_array($val, ['all', 'active', 'disabled'])) {
                        $returnParams[$param] = $val;
                    }
                    break;

                    // Always convert roleIds to array
                case 'reviewerIds':
                case 'roleIds':
                    if (is_string($val)) {
                        $val = explode(',', $val);
                    } elseif (!is_array($val)) {
                        $val = [$val];
                    }
                    $returnParams[$param] = array_map('intval', $val);
                    break;
                case 'assignedToCategory':
                case 'assignedToSection':
                case 'assignedToSubmissionStage':
                case 'assignedToSubmission':
                case 'reviewerRating':
                case 'reviewStage':
                case 'offset':
                case 'searchPhrase':
                    $returnParams[$param] = trim($val);
                    break;

                case 'reviewsCompleted':
                case 'reviewsActive':
                case 'daysSinceLastAssignment':
                case 'averageCompletion':
                    if (is_array($val)) {
                        $val = array_map('intval', $val);
                    } elseif (strpos($val, '-') !== false) {
                        $val = array_map('intval', explode('-', $val));
                    } else {
                        $val = [(int) $val];
                    }
                    $returnParams[$param] = $val;
                    break;

                    // Enforce a maximum count per request
                case 'count':
                    $returnParams[$param] = min(100, (int) $val);
                    break;
            }
        }

        return $returnParams;
    }

    /**
     * Get a single user
     *
     * @param   \Illuminate\Http\Request $request 
     * @return  \Illuminate\Http\JsonResponse
     */
    public function get(Request $request): JsonResponse
    {
        $userId = $request->route('userId', null);

        $user = Repo::user()->get($userId);

        if (!$user) {
            return response()->json([
                'error' => __('api.404.resourceNotFound')
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json(Repo::user()->getSchemaMap()->map($user), Response::HTTP_OK);
    }

    /**
     * Get a collection of users
     *
     * @param   \Illuminate\Http\Request $request 
     * @return  \Illuminate\Http\JsonResponse
     */
    public function getMany(Request $request): JsonResponse
    {
        $context = $request->attributes->get('context'); /** @var \PKP\context\Context $context */
        
        $params = $this->_processAllowedParams($request->query(null), [
            'assignedToCategory',
            'assignedToSection',
            'assignedToSubmission',
            'assignedToSubmissionStage',
            'count',
            'offset',
            'orderBy',
            'orderDirection',
            'roleIds',
            'searchPhrase',
            'status',
        ]);

        $params['contextId'] = $context->getId();

        Hook::call('API::users::params', [&$params, $request]);
        $collector = Repo::user()->getCollector();

        // Convert from $params array to what the Collector expects
        $orderBy = null;

        switch ($params['orderBy'] ?? 'id') {
            case 'id': $orderBy = $collector::ORDERBY_ID;
                break;
            case 'givenName': $orderBy = $collector::ORDERBY_GIVENNAME;
                break;
            case 'familyName': $orderBy = $collector::ORDERBY_FAMILYNAME;
                break;
            default: throw new Exception('Unknown orderBy specified');
        }
        
        $orderDirection = null;
        switch ($params['orderDirection'] ?? 'ASC') {
            case 'ASC': $orderDirection = $collector::ORDER_DIR_ASC;
                break;
            case 'DESC': $orderDirection = $collector::ORDER_DIR_DESC;
                break;
            default: throw new Exception('Unknown orderDirection specified');
        }
        
        $collector->assignedTo($params['assignedToSubmission'] ?? null, $params['assignedToSubmissionStage'] ?? null)
            ->assignedToSectionIds(isset($params['assignedToSection']) ? [$params['assignedToSection']] : null)
            ->assignedToCategoryIds(isset($params['assignedToCategory']) ? [$params['assignedToCategory']] : null)
            ->filterByRoleIds($params['roleIds'] ?? null)
            ->searchPhrase($params['searchPhrase'] ?? null)
            ->orderBy($orderBy, $orderDirection, [Locale::getLocale(), Application::get()->getRequest()->getSite()->getPrimaryLocale()])
            ->limit($params['count'] ?? null)
            ->offset($params['offset'] ?? null)
            ->filterByStatus($params['status'] ?? $collector::STATUS_ALL);
        
        $users = $collector->getMany();

        $map = Repo::user()->getSchemaMap();
        $items = [];
        foreach ($users as $user) {
            $items[] = $map->summarize($user);
        }

        return response()->json([
            'itemsMax' => $collector->limit(null)->offset(null)->getCount(),
            'items' => $items,
        ], Response::HTTP_OK);
    }

    /**
     * Get a collection of reviewers
     *
     * @param   \Illuminate\Http\Request $request 
     * @return  \Illuminate\Http\JsonResponse
     */
    public function getReviewers(Request $request): JsonResponse
    {
        $context = $request->attributes->get('context'); /** @var \PKP\context\Context $context */

        $params = $this->_processAllowedParams($request->query(), [
            'averageCompletion',
            'count',
            'daysSinceLastAssignment',
            'offset',
            'orderBy',
            'orderDirection',
            'reviewerRating',
            'reviewsActive',
            'reviewsCompleted',
            'reviewStage',
            'searchPhrase',
            'reviewerIds',
            'status',
        ]);

        Hook::call('API::users::reviewers::params', [&$params, $request]);

        $collector = Repo::user()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->includeReviewerData()
            ->filterByRoleIds([Role::ROLE_ID_REVIEWER])
            ->filterByWorkflowStageIds([$params['reviewStage']])
            ->searchPhrase($params['searchPhrase'] ?? null)
            ->filterByReviewerRating($params['reviewerRating'] ?? null)
            ->filterByReviewsCompleted($params['reviewsCompleted'][0] ?? null)
            ->filterByReviewsActive(...($params['reviewsActive'] ?? []))
            ->filterByDaysSinceLastAssignment(...($params['daysSinceLastAssignment'] ?? []))
            ->filterByAverageCompletion($params['averageCompletion'][0] ?? null)
            ->filterByUserIds($params['reviewerIds'] ?? null)
            ->limit($params['count'] ?? null)
            ->offset($params['offset'] ?? null);
        $usersCollection = $collector->getMany();
        $items = [];
        $map = Repo::user()->getSchemaMap();
        foreach ($usersCollection as $user) {
            $items[] = $map->summarizeReviewer($user);
        }

        return response()->json([
            'itemsMax' => $collector->limit(null)->offset(null)->getCount(),
            'items' => $items,
        ], Response::HTTP_OK);
    }

    /**
     * Retrieve the user report
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function getReport(Request $request): StreamedResponse|JsonResponse
    {
        $context = $request->attributes->get('context'); /** @var \PKP\context\Context $context */
        $params = ['contextIds' => [$context->getId()]];

        foreach ($request->query() as $param => $value) {
            switch ($param) {
                case 'userGroupIds':
                    if (is_string($value) && str_contains($value, ',')) {
                        $value = explode(',', $value);
                    } elseif (!is_array($value)) {
                        $value = [$value];
                    }
                    $params[$param] = array_map('intval', $value);
                    break;
                case 'mappings':
                    if (is_string($value) && str_contains($value, ',')) {
                        $value = explode(',', $value);
                    } elseif (!is_array($value)) {
                        $value = [$value];
                    }
                    $params[$param] = $value;
                    break;
            }
        }

        Hook::call('API::users::user::report::params', [&$params, $request]);

        $report = Repo::user()->getReport($params);

        return response()->streamDownload(
            function () use ($report) {
                $handle = fopen('php://output', 'w+');
                $report->serialize($handle);
                fclose($handle);
            },
            'user-report-' . date('Y-m-d') . '.csv',
            [
                'content-type: text/comma-separated-values',
                'content-disposition: attachment; filename="user-report-' . date('Y-m-d') . '.csv"',
            ]
        );

        // $response = new StreamedResponse(
        //     null,
        //     Response::HTTP_OK,
        // );

        // $response->setCallback(function () use ($report) {
        //     $handle = fopen('php://output', 'w+');
        //     $report->serialize($handle);
        //     fclose($handle);
        // });

        // $name = 'user-report-' . date('Y-m-d') . '.csv';

        // $response->headers->set('Content-Type', 'application/force-download');
        // $response->headers->set(
        //     'Content-Disposition',
        //     $response->headers->makeDisposition(
        //         'attachment',
        //         $name,
        //         str_replace('%', '', Str::ascii($name))
        //     )
        // );

        // return $response;
    }
}

class PKPUserHandler extends APIHandler
{
    public function __construct()
    {
        $this->_handlerPath = 'users';

        $roles = implode('|', [Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR]);

        app('router')->group(
            [
                'prefix' => $this->getEndpointPattern(),
                'middleware' => [
                    "has.user",
                    "has.context",
                    "has.roles:{$roles}",
                ],
            ],
            function () {
                app('router')
                    ->name('getReviewers')
                    ->get('reviewers', [UserController::class, 'getReviewers']);

                app('router')
                    ->name('getReport')
                    ->get('report', [UserController::class, 'getReport']);
                
                app('router')
                    ->name('getUser')
                    ->get('{userId}', [UserController::class, 'get']);

                app('router')
                    ->name('getManyUsers')
                    ->get('', [UserController::class, 'getMany']);
            }
        );

        parent::__construct();
    }
}