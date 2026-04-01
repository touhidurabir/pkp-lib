<?php

/**
 * @file classes/pid/Ecli.php
 *
 * Copyright (c) 2025-2026 Simon Fraser University
 * Copyright (c) 2025-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Ecli
 *
 * @ingroup pid
 *
 * @brief Ecli class
 *
 * @see https://eur-lex.europa.eu/content/help/faq/ecli.html
 */

namespace PKP\pid;

class Ecli extends BasePid
{
    /** @copydoc BasePid::regexes */
    public const regexes = [
        // ECLI:NL:HR:2020:1234 https://eur-lex.europa.eu/legal-content/redirect/?urn=ecli:NL:HR:2020:1234
        '/(?:https?:\/\/eur-lex\.europa\.eu\/legal-content\/redirect\/\?urn=)?ECLI:[A-Z]{2}:[A-Z0-9.]+:\d{4}:[A-Z0-9.]+/i'
    ];

    /** @copydoc BasePid::validationRegexes */
    public const validationRegexes = [
        '/^ECLI:[A-Z]{2}:[A-Z0-9.]+:\d{4}:[A-Z0-9.]+$/i'
    ];

    /** @copydoc BasePid::urlPrefix */
    public const urlPrefix = 'https://eur-lex.europa.eu/legal-content/redirect/?urn=';

    /** @copydoc BasePid::alternatePrefixes */
    public const alternatePrefixes = [
        'https://eur-lex.europa.eu/legal-content/redirect/?urn=',
        'https://eur-lex.europa.eu/legal-content/redirect/?urn=',
    ];
}
