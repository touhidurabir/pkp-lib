<?php

/**
 * @file jobs/email/ReviewReminder.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewReminder
 *
 * @ingroup jobs
 *
 * @brief Class to handle a job to send an review reminder
 */

namespace PKP\jobs\email;

use APP\facades\Repo;
use APP\core\Services;
use Illuminate\Support\Facades\Mail;
use PKP\log\event\PKPSubmissionEventLogEntry;
use PKP\core\PKPApplication;
use PKP\core\Core;
use PKP\invitation\invitations\ReviewerAccessInvite;
use PKP\mail\mailables\ReviewResponseRemindAuto;
use PKP\mail\mailables\ReviewRemindAuto;
use PKP\jobs\BaseJob;


class ReviewReminder extends BaseJob
{
    protected int $reviewAssignmentId;
    protected int $submissionId;
    protected int $contextId;
    protected string $mailableClass;

    public function __construct(int $reviewAssignmentId, int $submissionId, int $contextId, string $mailableClass)
    {
        parent::__construct();

        $this->reviewAssignmentId = $reviewAssignmentId;
        $this->submissionId = $submissionId;
        $this->contextId = $contextId;
        $this->mailableClass = $mailableClass;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $reviewAssignment = Repo::reviewAssignment()->get($this->reviewAssignmentId);
        $reviewer = Repo::user()->get($reviewAssignment->getReviewerId());
        
        if (!isset($reviewer)) {
            return;
        }

        $submission = Repo::submission()->get($this->submissionId);
        
        $contextService = Services::get('context');
        $context = $contextService->get($this->contextId);

        /** @var ReviewRemindAuto|ReviewResponseRemindAuto $mailable */
        $mailable = new $this->mailableClass($context, $submission, $reviewAssignment);

        $primaryLocale = $context->getPrimaryLocale();
        $emailTemplate = Repo::emailTemplate()->getByKey(
            $context->getId(), 
            $mailable::getEmailTemplateKey()
        );
        $mailable->subject($emailTemplate->getLocalizedData('subject', $primaryLocale))
            ->body($emailTemplate->getLocalizedData('body', $primaryLocale))
            ->from($context->getData('contactEmail'), $context->getData('contactName'))
            ->recipients([$reviewer]);

        $mailable->setData($primaryLocale);

        $reviewerAccessKeysEnabled = $context->getData('reviewerAccessKeysEnabled');
        if ($reviewerAccessKeysEnabled) { // Give one-click access if enabled
            $reviewInvitation = new ReviewerAccessInvite(
                $reviewAssignment->getReviewerId(),
                $context->getId(),
                $reviewAssignment->getId()
            );
            $reviewInvitation->setMailable($mailable);
            $reviewInvitation->dispatch();
        }

        // deprecated template variables OJS 2.x
        $mailable->addData([
            'messageToReviewer' => __('reviewer.step1.requestBoilerplate'),
            'abstractTermIfEnabled' => ($submission->getCurrentPublication()->getLocalizedData('abstract') == '' ? '' : __('common.abstract')),
        ]);

        Mail::send($mailable);

        Repo::reviewAssignment()->edit($reviewAssignment, [
            'dateReminded' => Core::getCurrentDate(),
            'reminderWasAutomatic' => 1
        ]);

        $eventLog = Repo::eventLog()->newDataObject([
            'assocType' => PKPApplication::ASSOC_TYPE_SUBMISSION,
            'assocId' => $submission->getId(),
            'eventType' => PKPSubmissionEventLogEntry::SUBMISSION_LOG_REVIEW_REMIND_AUTO,
            'userId' => null,
            'message' => 'submission.event.reviewer.reviewerRemindedAuto',
            'isTranslated' => false,
            'dateLogged' => Core::getCurrentDate(),
            'recipientId' => $reviewer->getId(),
            'recipientName' => $reviewer->getFullName(),
        ]);
        Repo::eventLog()->add($eventLog);
    }
}