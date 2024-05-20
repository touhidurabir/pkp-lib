<?php

/**
 * @file pages/about/AboutContextHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AboutContextHandler
 *
 * @ingroup pages_about
 *
 * @brief Handle requests for context-level about functions.
 */

namespace PKP\pages\about;

use APP\core\Application;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\template\TemplateManager;
use DateTime;
use PKP\core\PKPContainer;
use PKP\plugins\Hook;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\Role;
use PKP\userGroup\relationships\enums\UserUserGroupMastheadStatus;
use PKP\userGroup\relationships\UserUserGroup;

class AboutContextHandler extends Handler
{
    /**
     * @see PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $context = $request->getContext();
        if (!$context || !$context->getData('restrictSiteAccess')) {
            $templateMgr = TemplateManager::getManager($request);
            $templateMgr->setCacheability(TemplateManager::CACHEABILITY_PUBLIC);
        }

        $this->addPolicy(new ContextRequiredPolicy($request));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Display about page.
     *
     * @param array $args
     * @param \PKP\core\PKPRequest $request
     */
    public function index($args, $request)
    {
        // dd(BASE_SYS_DIR);
        $container = PKPContainer::getInstance();

        // we have to bind our app class to the interface
        // as the blade compiler needs the `getNamespace()` method to guess Blade component FQCNs
        // $container->instance(\Illuminate\Contracts\Foundation\Application::class, $container);

        // Configuration
        // Note that you can set several directories where your templates are located
        // The one listed as last will have higher priority
        $pathsToTemplates = [
            BASE_SYS_DIR . '/lib/pkp/templates',
            BASE_SYS_DIR . '/templates',
        ];
        $pathToCompiledTemplates = BASE_SYS_DIR . '/compiled';

        // Dependencies
        $filesystem = new \Illuminate\Filesystem\Filesystem;
        $eventDispatcher = new \Illuminate\Events\Dispatcher($container);

        // Create View Factory capable of rendering PHP and Blade templates
        $viewResolver = new \Illuminate\View\Engines\EngineResolver;
        $bladeCompiler = new \Illuminate\View\Compilers\BladeCompiler($filesystem, $pathToCompiledTemplates);
        
        $viewResolver->register('blade', function () use ($bladeCompiler) {
            return new \Illuminate\View\Engines\CompilerEngine($bladeCompiler);
        });
        
        $viewFinder = new \Illuminate\View\FileViewFinder($filesystem, $pathsToTemplates);
        $viewFactory = new \Illuminate\View\Factory($viewResolver, $viewFinder, $eventDispatcher);
        $viewFactory->setContainer($container);
        // \Illuminate\Support\Facades\Facade::setFacadeApplication($container);
        $container->instance(\Illuminate\Contracts\View\Factory::class, $viewFactory);
        $container->alias(
            \Illuminate\Contracts\View\Factory::class, 
            (new class extends \Illuminate\Support\Facades\View {
                public static function getFacadeAccessor() { return parent::getFacadeAccessor(); }
            })::getFacadeAccessor()
        );
        $container->instance(\Illuminate\View\Compilers\BladeCompiler::class, $bladeCompiler);
        $container->alias(
            \Illuminate\View\Compilers\BladeCompiler::class, 
            (new class extends \Illuminate\Support\Facades\Blade {
                public static function getFacadeAccessor() { return parent::getFacadeAccessor(); }
            })::getFacadeAccessor()
        );

        // Render template with page.blade.php
        // we can also use $viewFactory->make(...)->render();
        echo view('about', [
            'title' => 'Title',
            'text' => 'This is my text!',
        ]);
        
        
        // Original Code Implementation
        // $templateMgr = TemplateManager::getManager($request);
        // $this->setupTemplate($request);
        // $templateMgr->display('frontend/pages/about.tpl');
    }

    /**
     * Display editorial masthead page.
     *
     * @param array $args
     * @param \PKP\core\PKPRequest $request
     *
     * @hook AboutContextHandler::editorialMasthead [[$mastheadRoles, $mastheadUsers, $reviewers, $previousYear]]
     */
    public function editorialMasthead($args, $request)
    {
        $context = $request->getContext();

        $savedMastheadUserGroupIdsOrder = (array) $context->getData('mastheadUserGroupIds');

        $collector = Repo::userGroup()->getCollector();
        $allMastheadUserGroups = $collector
            ->filterByContextIds([$context->getId()])
            ->filterByMasthead(true)
            ->orderBy($collector::ORDERBY_ROLE_ID)
            ->getMany()
            ->toArray();

        // sort the masthead roles in their saved order for display
        $mastheadRoles = array_replace(array_flip($savedMastheadUserGroupIdsOrder), $allMastheadUserGroups);

        $mastheadUsers = [];
        foreach ($mastheadRoles as $mastheadUserGroup) {
            // Get all users that are active in the given role
            // and that have accepted to be displayed on the masthead for that role.
            // No need to filter by context ID, because the user groups are already filtered so.
            $users = Repo::user()
                ->getCollector()
                ->filterByUserGroupIds([$mastheadUserGroup->getId()])
                ->filterByUserUserGroupMastheadStatus(UserUserGroupMastheadStatus::STATUS_ON)
                ->getMany();
            foreach ($users as $user) {
                $userUserGroup = UserUserGroup::withUserId($user->getId())
                    ->withUserGroupId($mastheadUserGroup->getId())
                    ->withActive()
                    ->withMasthead()
                    ->first();
                if ($userUserGroup) {
                    $startDatetime = new DateTime($userUserGroup->dateStart);
                    $mastheadUsers[$mastheadUserGroup->getId()][$user->getId()] = [
                        'user' => $user,
                        'dateStart' => $startDatetime->format('Y'),
                    ];
                }
            }
        }

        $previousYear = date('Y') - 1;
        $reviewerIds = Repo::reviewAssignment()->getReviewerIdsByCompletedYear($context->getId(), $previousYear);
        $reviewers = Repo::user()
            ->getCollector()
            ->filterByUserIds($reviewerIds->toArray())
            ->getMany()
            ->all();

        Hook::call('AboutContextHandler::editorialMasthead', [$mastheadRoles, $mastheadUsers, $reviewers, $previousYear]);

        // To come after https://github.com/pkp/pkp-lib/issues/9771
        // $orcidIcon = OrcidManager::getIcon();
        $orcidIcon = '';

        $templateMgr = TemplateManager::getManager($request);
        $this->setupTemplate($request);
        $templateMgr->assign([
            'mastheadRoles' => $mastheadRoles,
            'mastheadUsers' => $mastheadUsers,
            'reviewers' => $reviewers,
            'previousYear' => $previousYear,
            'orcidIcon' => $orcidIcon
        ]);
        $templateMgr->display('frontend/pages/editorialMasthead.tpl');
    }

    /**
     * Display editorialTeam page.
     *
     * @param array $args
     * @param \PKP\core\PKPRequest $request
     */
    public function editorialTeam($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $this->setupTemplate($request);
        $templateMgr->display('frontend/pages/editorialTeam.tpl');
    }

    /**
     * Display submissions page.
     *
     * @param array $args
     * @param \PKP\core\PKPRequest $request
     */
    public function submissions($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $context = $request->getContext();
        $this->setupTemplate($request);


        $templateMgr->assign('submissionChecklist', $context->getLocalizedData('submissionChecklist'));

        // Get sections for this context
        $canSubmitAll = false;
        $userRoles = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES);
        if ($userRoles && !empty(array_intersect([Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR], $userRoles))) {
            $canSubmitAll = true;
        }

        $sections = Repo::section()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->excludeEditorOnly(!$canSubmitAll)
            ->getMany()
            ->all();

        $templateMgr->assign('sections', $sections);
        $templateMgr->display('frontend/pages/submissions.tpl');
    }

    /**
     * Display contact page.
     *
     * @param array $args
     * @param \PKP\core\PKPRequest $request
     */
    public function contact($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $this->setupTemplate($request);
        $context = $request->getContext();
        $templateMgr->assign([
            'mailingAddress' => $context->getData('mailingAddress'),
            'contactPhone' => $context->getData('contactPhone'),
            'contactEmail' => $context->getData('contactEmail'),
            'contactName' => $context->getData('contactName'),
            'supportName' => $context->getData('supportName'),
            'supportPhone' => $context->getData('supportPhone'),
            'supportEmail' => $context->getData('supportEmail'),
            'contactTitle' => $context->getLocalizedData('contactTitle'),
            'contactAffiliation' => $context->getLocalizedData('contactAffiliation'),
        ]);
        $templateMgr->display('frontend/pages/contact.tpl');
    }
}
