<?php

// The Raspberry Pi fetches these settings using the same token as input.php.
$config = require __DIR__ . '/../config/config.php';
$token = (string) ($config['api']['raspberry_key'] ?? '');

header('Cache-Control: no-store');
if (strlen($token) < 16 || !hash_equals($token, $_SERVER['HTTP_X_RASPBERRY_TOKEN'] ?? '')) {
    http_response_code(404);
    exit;
}

// The local PHP file returns the Pi settings as text, including the camera credentials.
$localConfig = __DIR__ . '/../config/raspberry.local.php';
if (!is_file($localConfig)) {
    http_response_code(503);
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo require $localConfig;
