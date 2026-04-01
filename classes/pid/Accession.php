<?php

/**
 * @file classes/pid/Accession.php
 *
 * Copyright (c) 2025-2026 Simon Fraser University
 * Copyright (c) 2025-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Accession
 *
 * @ingroup pid
 *
 * @brief Accession class
 *
 * @brief Accession numbers are repository-specific identifiers with no standard format.
 * No validation regex is provided — any non-empty value is considered valid.
 */

namespace PKP\pid;

class Accession extends BasePid
{
    // No regexes, prefix, urlPrefix or alternatePrefixes —
    // accession numbers are repository-specific with no standard format.
}
