<?php

// Load Composer autoloader
require_once './lib/vendor/autoload.php';

// Define the starting index.php file path
define('INDEX_FILE_LOCATION', dirname(__FILE__, 3) . '/index.php');

define('BASE_SYS_DIR', dirname(INDEX_FILE_LOCATION));
chdir(BASE_SYS_DIR);

// System-wide functions
require_once 'includes/functions.inc.php';

// Register custom autoloader functions for namespaces
spl_autoload_register(function ($class) {
    $prefix = 'PKP\\';
    $rootPath = BASE_SYS_DIR . '/lib/pkp/classes';
    customAutoload($rootPath, $prefix, $class);
});
spl_autoload_register(function ($class) {
    $prefix = 'APP\\';
    $rootPath = BASE_SYS_DIR . '/classes';
    customAutoload($rootPath, $prefix, $class);
});

return new \APP\core\Application();
