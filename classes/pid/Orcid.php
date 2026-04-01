<?php

/**
 * @file classes/pid/Orcid.php
 *
 * Copyright (c) 2025-2026 Simon Fraser University
 * Copyright (c) 2025-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Orcid
 *
 * @ingroup pid
 *
 * @brief Orcid class
 */

namespace PKP\pid;

class Orcid extends BasePid
{
    /** @copydoc BasePid::regexes */
    public const regexes = [
        // orcid:0000-0002-1694-233X https://orcid.org/0000-0002-1694-233X
        '/(?:orcid:\s*|https?:\/\/orcid\.org\/)\d{4}-\d{4}-\d{4}-\d{1,4}[0-9X]/i'
    ];

    /** @copydoc BasePid::validationRegexes */
    public const validationRegexes = [
        '/^\d{4}-\d{4}-\d{4}-\d{1,4}[0-9X]$/i'
    ];

    /** @copydoc BasePid::prefix */
    public const prefix = 'orcid:';

    /** @copydoc BasePid::urlPrefix */
    public const urlPrefix = 'https://orcid.org/';

    /** @copydoc BasePid::alternatePrefixes */
    public const alternatePrefixes = [
        'orcid',
        'orcidId',
        'orcidId:',
        'orcid_id',
        'orcid_id:'
    ];
}
