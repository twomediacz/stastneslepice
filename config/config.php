<?php

/**
 * Hlavní konfigurace aplikace.
 * Pro lokální přepisy vytvořte soubor config.local.php (je v .gitignore).
 */

$config = [
    'app' => [
        'name' => 'Šťastné slepice',
        'locale' => 'Doloplazy',
        'timezone' => 'Europe/Prague',
        'debug' => false,
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'stastneslepice',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];

// Lokální přepisy (mimo git)
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    $local = require $localConfig;
    $config = array_replace_recursive($config, $local);
}

return $config;
