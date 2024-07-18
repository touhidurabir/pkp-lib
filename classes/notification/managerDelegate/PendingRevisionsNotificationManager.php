<?php

/**
 * @file classes/notification/managerDelegate/PendingRevisionsNotificationManager.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PendingRevisionsNotificationManager
 *
 * @ingroup managerDelegate
 *
 * @brief Pending revision notification types manager delegate.
 */

namespace PKP\notification\managerDelegate;

use APP\core\Application;
use APP\decision\Decision;
use APP\facades\Repo;
use APP\notification\Notification;
use PKP\controllers\api\file\linkAction\AddRevisionLinkAction;
use PKP\core\PKPRequest;
use PKP\db\DAORegistry;
use PKP\notification\NotificationDAO;
use PKP\notification\NotificationManagerDelegate;
use PKP\notification\PKPNotification;
use PKP\security\Role;
use PKP\submission\reviewRound\ReviewRoundDAO;
use PKP\workflow\WorkflowStageDAO;

class PendingRevisionsNotificationManager extends NotificationManagerDelegate
{
    /**
     * @copydoc PKPNotificationOperationManager::getStyleClass()
     */
    public function getStyleClass(PKPNotification $notification): string
    {
        return NOTIFICATION_STYLE_CLASS_WARNING;
    }

    /**
     * @copydoc PKPNotificationOperationManager::getNotificationUrl()
     */
    public function getNotificationUrl(PKPRequest $request, PKPNotification $notification): ?string
    {
        $submission = Repo::submission()->get($notification->getAssocId());
        return Repo::submission()->getWorkflowUrlByUserRoles($submission, $notification->getUserId());
    }

    /**
     * @copydoc PKPNotificationOperationManager::getNotificationMessage()
     */
    public function getNotificationMessage(PKPRequest $request, PKPNotification $notification): ?string
    {
        $stageData = $this->_getStageDataByType();
        $stageKey = $stageData['translationKey'];

        return __('notification.type.pendingRevisions', ['stage' => __($stageKey)]);
    }

    /**
     * @copydoc PKPNotificationOperationManager::getNotificationContents()
     */
    public function getNotificationContents(PKPRequest $request, PKPNotification $notification): mixed
    {
        $stageData = $this->_getStageDataByType();
        $stageId = $stageData['id'];
        $submissionId = $notification->getAssocId();

        $submission = Repo::submission()->get($submissionId);
        $reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO'); /** @var ReviewRoundDAO $reviewRoundDao */
        $lastReviewRound = $reviewRoundDao->getLastReviewRoundBySubmissionId($submission->getId(), $stageId);

        $uploadFileAction = new AddRevisionLinkAction(
            $request,
            $lastReviewRound,
            [Role::ROLE_ID_AUTHOR]
        );

        return $this->fetchLinkActionNotificationContent($uploadFileAction, $request);
    }

    /**
     * @copydoc PKPNotificationOperationManager::getNotificationTitle()
     */
    public function getNotificationTitle(PKPNotification $notification): string
    {
        $stageData = $this->_getStageDataByType();
        $stageKey = $stageData['translationKey'];
        return __('notification.type.pendingRevisions.title', ['stage' => __($stageKey)]);
    }

    /**
     * @copydoc NotificationManagerDelegate::updateNotification()
     */
    public function updateNotification(PKPRequest $request, ?array $userIds, int $assocType, int $assocId): void
    {
        $userId = current($userIds);
        $submissionId = $assocId;
        $stageData = $this->_getStageDataByType();
        if ($stageData == null) {
            return;
        }
        $expectedStageId = $stageData['id'];

        $pendingRevisionDecision = Repo::decision()->getActivePendingRevisionsDecision($submissionId, $expectedStageId, Decision::PENDING_REVISIONS);
        $removeNotifications = false;

        if ($pendingRevisionDecision) {
            if (Repo::decision()->revisionsUploadedSinceDecision($pendingRevisionDecision, $submissionId)) {
                // Some user already uploaded a revision. Flag to delete any existing notification.
                $removeNotifications = true;
            } else {
                $context = $request->getContext();
                $notificationDao = DAORegistry::getDAO('NotificationDAO'); /** @var NotificationDAO $notificationDao */
                $notificationFactory = $notificationDao->getByAssoc(
                    Application::ASSOC_TYPE_SUBMISSION,
                    $submissionId,
                    $userId,
                    PKPNotification::NOTIFICATION_TYPE_EDITOR_DECISION_PENDING_REVISIONS,
                    $context->getId()
                );
                if (!$notificationFactory->next()) {
                    // Create or update a pending revision task notification.
                    $notificationDao = DAORegistry::getDAO('NotificationDAO'); /** @var NotificationDAO $notificationDao */
                    $notificationDao->build(
                        $context->getId(),
                        Notification::NOTIFICATION_LEVEL_TASK,
                        $this->getNotificationType(),
                        Application::ASSOC_TYPE_SUBMISSION,
                        $submissionId,
                        $userId
                    );
                }
            }
        } else {
            // No pending revision decision or other later decision overriden it.
            // Flag to delete any existing notification.
            $removeNotifications = true;
        }

        if ($removeNotifications) {
            $context = $request->getContext();
            $notificationDao = DAORegistry::getDAO('NotificationDAO'); /** @var NotificationDAO $notificationDao */
            $notificationDao->deleteByAssoc(Application::ASSOC_TYPE_SUBMISSION, $submissionId, $userId, $this->getNotificationType(), $context->getId());
            $notificationDao->deleteByAssoc(Application::ASSOC_TYPE_SUBMISSION, $submissionId, $userId, PKPNotification::NOTIFICATION_TYPE_EDITOR_DECISION_PENDING_REVISIONS, $context->getId());
        }
    }


    //
    // Private helper methods.
    //
    /**
     * Get the data for an workflow stage by pending revisions notification type.
     */
    private function _getStageDataByType(): ?array
    {
        $stagesData = WorkflowStageDAO::getWorkflowStageKeysAndPaths();

        return match ($this->getNotificationType()) {
            PKPNotification::NOTIFICATION_TYPE_PENDING_INTERNAL_REVISIONS => $stagesData[WORKFLOW_STAGE_ID_INTERNAL_REVIEW] ?? null,
            PKPNotification::NOTIFICATION_TYPE_PENDING_EXTERNAL_REVISIONS => $stagesData[WORKFLOW_STAGE_ID_EXTERNAL_REVIEW] ?? null,
        };
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\notification\managerDelegate\PendingRevisionsNotificationManager', '\PendingRevisionsNotificationManager');
}
