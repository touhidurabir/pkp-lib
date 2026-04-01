<?php

/**
 * @file classes/pid/Isbn.php
 *
 * Copyright (c) 2025-2026 Simon Fraser University
 * Copyright (c) 2025-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Isbn
 *
 * @ingroup pid
 *
 * @brief Isbn class
 *
 * @see https://www.isbn-international.org/content/what-isbn
 */

namespace PKP\pid;

class Isbn extends BasePid
{
    /** @copydoc BasePid::regexes */
    public const regexes = [
        // isbn:978-0-306-40615-7 isbn:9780306406157 isbn:030640615X
        '/(?:isbn:\s*)(?:97[89]-?)?(?:\d-?){9}[\dX]/i'
    ];

    /** @copydoc BasePid::validationRegexes */
    public const validationRegexes = [
        '/^(?:97[89]-?)?(?:\d-?){9}[\dX]$/i'
    ];

    /** @copydoc BasePid::prefix */
    public const prefix = 'isbn:';

    /** @copydoc BasePid::alternatePrefixes */
    public const alternatePrefixes = [
        'isbn',
    ];
}
