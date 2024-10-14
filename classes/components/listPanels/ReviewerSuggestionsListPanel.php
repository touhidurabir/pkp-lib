<?php
/**
 * @file classes/components/listPanels/ReviewerSuggestionsListPanel.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerSuggestionsListPanel
 *
 * @brief 
 */

namespace PKP\components\listPanels;

use APP\core\Application;
use APP\submission\Submission;
use PKP\components\forms\publication\ReviewerSuggestionsForm;
use PKP\context\Context;

class ReviewerSuggestionsListPanel extends ListPanel
{
    public Submission $submission;
    public Context $context;
    public array $locales;

    /** Whether the user can edit the current publication */
    public bool $canEditPublication;

    public function __construct(
        string $id,
        string $title,
        Submission $submission,
        Context $context,
        array $locales,
        array $items = [],
        bool $canEditPublication = false
    ) {
        parent::__construct($id, $title);
        $this->submission = $submission;
        $this->context = $context;
        $this->locales = $locales;
        $this->items = $items;
        $this->canEditPublication = $canEditPublication;
    }

    /**
     * @copydoc ListPanel::getConfig()
     */
    public function getConfig()
    {
        $config = parent::getConfig();

        // Remove some props not used in this list panel
        unset($config['description']);
        unset($config['expanded']);
        unset($config['headingLevel']);

        $config = array_merge(
            $config,
            [
                'canEditPublication' => $this->canEditPublication,
                'publicationApiUrlFormat' => $this->getPublicationUrlFormat(),
                'reviewerSuggestionsApiUrl' => $this->getReviewerSuggestionsApiUrl(),
                'form' => $this->getLocalizedForm(),
                'items' => $this->items,
            ]
        );

        return $config;
    }

    protected function getReviewerSuggestionsApiUrl(): string
    {
        return Application::get()->getRequest()->getDispatcher()->url(
            Application::get()->getRequest(),
            Application::ROUTE_API,
            $this->context->getPath(),
            "submissions/{$this->submission->getId()}/reviewers/suggestions"
        );
    }

    /**
     * Get an example of the url to a publication's API endpoint,
     * with a placeholder instead of the publication id, eg:
     *
     * http://example.org/api/v1/submissions/1/publications/__publicationId__
     */
    protected function getPublicationUrlFormat(): string
    {
        return Application::get()->getRequest()->getDispatcher()->url(
            Application::get()->getRequest(),
            Application::ROUTE_API,
            $this->context->getPath(),
            'submissions/' . $this->submission->getId() . '/publications/__publicationId__'
        );
    }

    /**
     * Get the form data localized to the submission's locale
     */
    protected function getLocalizedForm(): array
    {
        $apiUrl = $this->getReviewerSuggestionsApiUrl();

        $submissionLocale = $this->submission->getData('locale');
        $data = $this->getForm($apiUrl)->getConfig();

        $data['primaryLocale'] = $submissionLocale;
        $data['visibleLocales'] = [$submissionLocale];
        $data['supportedFormLocales'] = collect($this->locales)
            ->sortBy([fn (array $a, array $b) => $b['key'] === $submissionLocale ? 1 : -1])
            ->values()
            ->toArray();

        return $data;
    }

    /**
     * Get the reviewer suggestions form
     */
    protected function getForm(string $url): ReviewerSuggestionsForm
    {
        return new ReviewerSuggestionsForm(
            $url,
            $this->locales,
            $this->submission,
            $this->context
        );
    }
}
