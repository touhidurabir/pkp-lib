<?php

/**
 * @file classes/security/authorization/NoteAccessPolicy.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NoteAccessPolicy
 *
 * @ingroup security_authorization
 *
 * @brief Class to control access to a note.
 *
 * NB: This policy expects previously authorized submission, query and
 * accessible workflow stages in the authorization context.
 */

namespace PKP\security\authorization;

use APP\core\Application;
use APP\core\Request;
use PKP\core\PKPRequest;
use PKP\note\Note;
use PKP\security\Role;

class NoteAccessPolicy extends AuthorizationPolicy
{
    public const NOTE_ACCESS_READ = 1;
    public const NOTE_ACCESS_WRITE = 2;

    /** @var Request */
    private $_request;

    /** @var int */
    private $_noteId;

    /** @var int */
    private $_accessMode;

    /**
     * Constructor
     *
     * @param PKPRequest $request
     * @param int $noteId
     * @param int $accessMode NOTE_ACCESS_...
     */
    public function __construct($request, $noteId, $accessMode)
    {
        parent::__construct('user.authorization.unauthorizedNote');
        $this->_request = $request;
        $this->_noteId = $noteId;
        $this->_accessMode = $accessMode;
    }

    //
    // Implement template methods from AuthorizationPolicy
    //
    /**
     * @see AuthorizationPolicy::effect()
     */
    public function effect(): int
    {
        if (!$this->_noteId) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        $query = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_QUERY);
        $submission = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);
        $assignedStages = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ACCESSIBLE_WORKFLOW_STAGES);

        if (!$query || !$submission || empty($assignedStages)) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        $note = Note::find($this->_noteId);

        if (!$note instanceof \PKP\note\Note) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        // Note, query, submission and assigned stages must match
        if ($note->assocId != $query->id
                || $note->assocType != Application::ASSOC_TYPE_QUERY
                || $query->assocId != $submission->getId()
                || $query->assocType != Application::ASSOC_TYPE_SUBMISSION
                || !array_key_exists($query->stageId, $assignedStages)
                || empty($assignedStages[$query->stageId])) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }


        // Headnotes can be edited by managers, site admins and sub editors anytime.
        // Other users can only edit their own headnotes within 1 hour of creation.
        if ($this->_accessMode === self::NOTE_ACCESS_WRITE) {
            $user = $this->_request->getUser();

            if (!$note->isHeadnote) {
                return AuthorizationPolicy::AUTHORIZATION_DENY;
            }

            if (array_intersect($assignedStages[$query->stageId], [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_SUB_EDITOR])) {
                $this->addAuthorizedContextObject(Application::ASSOC_TYPE_NOTE, $note);
                return AuthorizationPolicy::AUTHORIZATION_PERMIT;
            }

            if ($note->userId != $user->getId()) {
                return AuthorizationPolicy::AUTHORIZATION_DENY;
            }

            if (time() - strtotime($note->dateCreated) >= 3600) {
                return AuthorizationPolicy::AUTHORIZATION_DENY;
            }
        }

        $this->addAuthorizedContextObject(Application::ASSOC_TYPE_NOTE, $note);

        return AuthorizationPolicy::AUTHORIZATION_PERMIT;
    }
}
