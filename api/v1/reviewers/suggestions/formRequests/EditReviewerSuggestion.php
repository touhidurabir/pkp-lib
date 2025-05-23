<?php

/**
 * @file api/v1/reviewers/suggestions/formRequests/EditReviewerSuggestion.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class EditReviewerSuggestion
 *
 * @brief Handle API requests validation for updating reviewer suggestion operations.
 *
 */

namespace PKP\API\v1\reviewers\suggestions\formRequests;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use PKP\submission\reviewer\suggestion\ReviewerSuggestion;
use PKP\API\v1\reviewers\suggestions\formRequests\AddReviewerSuggestion;

class EditReviewerSuggestion extends AddReviewerSuggestion
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'familyName' => [
                'required',
            ],
            'givenName' => [
                'required',
            ],
            'email' => [
                'required',
                'email',
                Rule::unique((new ReviewerSuggestion)->getTable())
                    ->ignore($this->route('suggestionId'), (new ReviewerSuggestion)->getKeyName())
                    ->where(fn (Builder $query) => $query->where('submission_id', [$this->route('submissionId')])),
            ],
            'affiliation' => [
                'required',
            ],
            'suggestionReason' => [
                'required',
            ],
            'orcidId' => [
                'sometimes',
                'nullable',
                'string',
                'orcid',
            ],
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        if (!ReviewerSuggestion::find($this->route('suggestionId'))) {
            throw new HttpResponseException(response()->json([
                'error' => __('api.404.resourceNotFound'),
            ], Response::HTTP_NOT_FOUND));
        }
    }
}
