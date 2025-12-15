<?php
/**
 * @file includes/phpstan-bootstrap.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Additional bootstrap for PHPStan static analysis.
 * Defines constants and stubs that may not be available during static analysis.
 */

// Define INDEX_FILE_LOCATION if not already defined
if (!defined('INDEX_FILE_LOCATION')) {
    define('INDEX_FILE_LOCATION', dirname(__DIR__, 2) . '/index.php');
}

// Add any application constants that cause PHPStan errors here
// Example constants from OJS/OPS/OMP that might not be defined during static analysis:

/*
 * Review assignment statuses
 */
if (!defined('REVIEW_ASSIGNMENT_STATUS_AWAITING_RESPONSE')) {
    define('REVIEW_ASSIGNMENT_STATUS_AWAITING_RESPONSE', 0);
}
if (!defined('REVIEW_ASSIGNMENT_STATUS_DECLINED')) {
    define('REVIEW_ASSIGNMENT_STATUS_DECLINED', 1);
}
if (!defined('REVIEW_ASSIGNMENT_STATUS_RESPONSE_OVERDUE')) {
    define('REVIEW_ASSIGNMENT_STATUS_RESPONSE_OVERDUE', 4);
}

/*
 * Submission statuses - uncomment if needed
 */
// if (!defined('STATUS_QUEUED')) {
//     define('STATUS_QUEUED', 1);
// }
// if (!defined('STATUS_PUBLISHED')) {
//     define('STATUS_PUBLISHED', 3);
// }
// if (!defined('STATUS_DECLINED')) {
//     define('STATUS_DECLINED', 4);
// }
// if (!defined('STATUS_SCHEDULED')) {
//     define('STATUS_SCHEDULED', 5);
// }

/**
 * Note: Add more constants here as PHPStan discovers them
 * Run PHPStan and look for "Constant X not found" errors
 *
 * To find all constants in codebase:
 * grep -r "define(" lib/pkp/includes/ classes/ lib/pkp/classes/ | grep -v "//"
 */
