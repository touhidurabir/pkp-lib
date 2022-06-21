<?php

/**
 * @file classes/decision/types/BackToPreviousExternalReviewRound.inc.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class decision
 *
 * @brief A decision to back out to previous external review round from current round
 */

namespace PKP\decision\types;

use APP\core\Application;
use APP\decision\Decision;
use APP\submission\Submission;
use Illuminate\Validation\Validator;
use PKP\context\Context;
use PKP\db\DAORegistry;
use PKP\decision\DecisionType;
use PKP\decision\Steps;
use PKP\decision\steps\Email;
use PKP\decision\types\contracts\DecisionRetractable;
use PKP\decision\types\traits\InExternalReviewRound;
use PKP\decision\types\traits\NotifyAuthors;
use PKP\decision\types\traits\NotifyReviewers;
use PKP\decision\types\traits\withReviewRound;
use PKP\mail\mailables\DecisionBackToPreviousExternalReviewRoundNotifyAuthor;
use PKP\mail\mailables\DecisionBackToPreviousExternalReviewRoundNotifyReviewer;
use PKP\security\Role;
use PKP\submission\reviewAssignment\ReviewAssignmentDAO;
use PKP\submission\reviewRound\ReviewRound;
use PKP\user\User;

class BackToPreviousExternalReviewRound extends DecisionType implements DecisionRetractable
{
    use NotifyReviewers;
    use NotifyAuthors;
    use InExternalReviewRound;
    use withReviewRound;

    public function getDecision(): int
    {
        return Decision::BACK_TO_PREVIOUS_EXTERNAL_REVIEW_ROUND;
    }

    public function getNewStageId(): ?int
    {
        return WORKFLOW_STAGE_ID_EXTERNAL_REVIEW;
    }

    public function getNewStatus(): ?int
    {
        return null;
    }

    public function getNewReviewRoundStatus(): ?int
    {
        return null;
    }

    public function getLabel(?string $locale = null): string
    {
        return __('editor.submission.decision.backToPreviousExternalReviewRound', [], $locale);
    }

    public function getDescription(?string $locale = null): string
    {
        return __('editor.submission.decision.backToPreviousExternalReviewRound.description', [], $locale);
    }

    public function getLog(): string
    {
        return 'editor.submission.decision.backToPreviousExternalReviewRound.log';
    }

    public function getCompletedLabel(): string
    {
        return __('editor.submission.decision.backToPreviousExternalReviewRound.completed');
    }

    public function getCompletedMessage(Submission $submission): string
    {
        return __('editor.submission.decision.backToPreviousExternalReviewRound.completed.description', ['title' => $submission->getLocalizedFullTitle()]);
    }

    public function validate(array $props, Submission $submission, Context $context, Validator $validator, ?int $reviewRoundId = null)
    {
        // If there is no review round id, a validation error will already have been set
        if (!$reviewRoundId) {
            return;
        }

        parent::validate($props, $submission, $context, $validator, $reviewRoundId);

        if (!isset($props['actions'])) {
            return;
        }

        if (!$this->canRetract($submission, $reviewRoundId)) {
            $validator->errors()->add('restriction', __('editor.submission.decision.backToPreviousExternalReviewRound.restriction'));
        }

        foreach ((array) $props['actions'] as $index => $action) {
            $actionErrorKey = 'actions.' . $index;
            switch ($action['id']) {
                case $this->ACTION_NOTIFY_REVIEWERS:
                    $this->validateNotifyReviewersAction($action, $actionErrorKey, $validator, $submission, $reviewRoundId, false);
                    break;
            }
        }
    }

    public function runAdditionalActions(Decision $decision, Submission $submission, User $editor, Context $context, array $actions)
    {
        parent::runAdditionalActions($decision, $submission, $editor, $context, $actions);

        foreach ($actions as $action) {
            switch ($action['id']) {
                case $this->ACTION_NOTIFY_AUTHORS:
                    $this->sendAuthorEmail(
                        new DecisionBackToPreviousExternalReviewRoundNotifyAuthor($context, $submission, $decision),
                        $this->getEmailDataFromAction($action),
                        $editor,
                        $submission,
                        $context
                    );
                    break;
                case $this->ACTION_NOTIFY_REVIEWERS:
                    $this->sendReviewersEmail(
                        new DecisionBackToPreviousExternalReviewRoundNotifyReviewer($context, $submission, $decision),
                        $this->getEmailDataFromAction($action),
                        $editor,
                        $submission
                    );
                    break;
            }
        }

        $request = Application::get()->getRequest();

        $reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO'); /** @var ReviewRoundDAO $reviewRoundDao */
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /** @var ReviewAssignmentDAO $reviewAssignmentDao */
        $reviewRoundId = (int)$request->getUserVar('reviewRoundId');

        $reviewAssignmentDao->deleteByReviewRoundId($reviewRoundId);
        $reviewRoundDao->deleteById($reviewRoundId);
    }

    public function getSteps(Submission $submission, Context $context, User $editor, ?ReviewRound $reviewRound): ?Steps
    {
        $steps = new Steps($this, $submission, $context, $reviewRound);

        $fakeDecision = $this->getFakeDecision($submission, $editor);
        $fileAttachers = $this->getFileAttachers($submission, $context);
        $reviewAssignments = $this->getActiveReviewAssignments($submission->getId(), $reviewRound->getId());

        $authors = $steps->getStageParticipants(Role::ROLE_ID_AUTHOR);
        if (count($authors)) {
            $mailable = new DecisionBackToPreviousExternalReviewRoundNotifyAuthor($context, $submission, $fakeDecision);
            $steps->addStep(new Email(
                $this->ACTION_NOTIFY_AUTHORS,
                __('editor.submission.decision.notifyAuthors'),
                __('editor.submission.decision.backToPreviousExternalReviewRound.notifyAuthorsDescription'),
                $authors,
                $mailable
                    ->sender($editor)
                    ->recipients($authors),
                $context->getSupportedFormLocales(),
                $fileAttachers
            ));
        }

        if (count($reviewAssignments)) {
            $reviewers = $steps->getReviewersFromAssignments($reviewAssignments);
            $mailable = new DecisionBackToPreviousExternalReviewRoundNotifyReviewer($context, $submission, $fakeDecision);
            $steps->addStep((new Email(
                $this->ACTION_NOTIFY_REVIEWERS,
                __('editor.submission.decision.notifyReviewers'),
                __('editor.submission.decision.backToPreviousExternalReviewRound.notifyReviewers.description'),
                $reviewers,
                $mailable->sender($editor),
                $context->getSupportedFormLocales(),
                $fileAttachers
            ))->canChangeRecipients(true));
        }

        return $steps;
    }

    /**
     * Determine if can back out form current external review round to previous external review round
     */
    public function canRetract(Submission $submission, ?int $reviewRoundId): bool
    {
        if (!$reviewRoundId) {
            return false;
        }

        // if no reviewer assigned , then back out is not proper choice
        // but to remove the round is the right choice
        if (!$this->hasReviewerAssigned($reviewRoundId)) {
            return false;
        }

        // if there is any submitted review by reviewer that is not cancelled
        // can not back out
        if ($this->hasConfirmedReviewer($submission, $reviewRoundId)) {
            return false;
        }

        // if this is the only round availabel
        // can not back out to previous round as there is none
        if ($this->isOnlyReviewRound($submission, WORKFLOW_STAGE_ID_EXTERNAL_REVIEW)) {
            return false;
        }

        // if there is any completed review by reviewer
        // can not back out
        if ($this->hasCompletedReviewAssginment($submission, $reviewRoundId)) {
            return false;
        }

        return true;
    }
}
