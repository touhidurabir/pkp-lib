<?php

/**
 * @file classes/pid/Issn.php
 *
 * Copyright (c) 2025-2026 Simon Fraser University
 * Copyright (c) 2025-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Issn
 *
 * @ingroup pid
 *
 * @brief Issn class
 *
 * @see https://www.issn.org/understanding-the-issn/what-is-an-issn/
 */

namespace PKP\pid;

class Issn extends BasePid
{
    /** @copydoc BasePid::regexes */
    public const regexes = [
        // issn:2167-8359 https://portal.issn.org/resource/ISSN/2167-8359
        '/(?:issn:\s*|https?:\/\/portal\.issn\.org\/resource\/ISSN\/)\d{4}-\d{3}[\dX]/i'
    ];

    /** @copydoc BasePid::validationRegexes */
    public const validationRegexes = [
        '/^\d{4}-\d{3}[\dX]$/i'
    ];

    /** @copydoc BasePid::prefix */
    public const prefix = 'issn:';

    /** @copydoc BasePid::urlPrefix */
    public const urlPrefix = 'https://portal.issn.org/resource/ISSN/';

    /** @copydoc BasePid::alternatePrefixes */
    public const alternatePrefixes = [
        'issn',
    ];
}
