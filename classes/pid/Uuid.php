<?php

/**
 * @file classes/pid/Uuid.php
 *
 * Copyright (c) 2025-2026 Simon Fraser University
 * Copyright (c) 2025-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Uuid
 *
 * @ingroup pid
 *
 * @brief Uuid class
 *
 * @see https://www.rfc-editor.org/rfc/rfc9562
 */

namespace PKP\pid;

class Uuid extends BasePid
{
    /** @copydoc BasePid::regexes */
    public const regexes = [
        // 550e8400-e29b-41d4-a716-446655440000
        '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i'
    ];

    /** @copydoc BasePid::validationRegexes */
    public const validationRegexes = [
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'
    ];
}
