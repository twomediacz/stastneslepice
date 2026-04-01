<?php

if (!defined('APP_TEST_MODE')) {
    define('APP_TEST_MODE', true);
}

$autoload = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoload)) {
    require_once $autoload;
    return;
}

require_once __DIR__ . '/../src/Core/autoload.php';
