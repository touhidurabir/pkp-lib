<?php

// Load Composer autoloader
require_once 'lib/vendor/autoload.php';

// System-wide functions
require_once './includes/functions.inc.php';

// Register custom autoloader functions for namespaces
spl_autoload_register(function ($class) {
    $prefix = 'PKP\\';
    $rootPath = 'classes';
    customAutoload($rootPath, $prefix, $class);
});
