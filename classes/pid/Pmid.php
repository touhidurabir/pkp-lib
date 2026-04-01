<?php

/**
 * @file classes/pid/Pmid.php
 *
 * Copyright (c) 2025-2026 Simon Fraser University
 * Copyright (c) 2025-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Pmid
 *
 * @ingroup pid
 *
 * @brief Pmid class
 *
 * @see https://pubmed.ncbi.nlm.nih.gov/
 */

namespace PKP\pid;

class Pmid extends BasePid
{
    /** @copydoc BasePid::regexes */
    public const regexes = [
        // pmid:12345678 https://pubmed.ncbi.nlm.nih.gov/12345678
        '/(?:pmid:\s*|https?:\/\/pubmed\.ncbi\.nlm\.nih\.gov\/)\d+/i'
    ];

    /** @copydoc BasePid::validationRegexes */
    public const validationRegexes = [
        '/^\d+$/'
    ];

    /** @copydoc BasePid::prefix */
    public const prefix = 'pmid:';

    /** @copydoc BasePid::urlPrefix */
    public const urlPrefix = 'https://pubmed.ncbi.nlm.nih.gov/';

    /** @copydoc BasePid::alternatePrefixes */
    public const alternatePrefixes = [
        'pmid',
        'pubmed',
        'pubmed:',
    ];
}
