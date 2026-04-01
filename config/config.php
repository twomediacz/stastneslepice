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
    'api' => [
        'climate_key' => '', // Nastavte v config.local.php
    ],
    'db' => [
        'driver' => getenv('DB_DRIVER') ?: 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => getenv('DB_DATABASE') ?: 'stastneslepice',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
];

if (($dbHost = getenv('DB_HOST')) !== false && $dbHost !== '') {
    $config['db']['host'] = $dbHost;
}

if (($dbPort = getenv('DB_PORT')) !== false && $dbPort !== '') {
    $config['db']['port'] = (int) $dbPort;
}

if (($dbCharset = getenv('DB_CHARSET')) !== false && $dbCharset !== '') {
    $config['db']['charset'] = $dbCharset;
}

// Lokální přepisy (mimo git)
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    $local = require $localConfig;
    $config = array_replace_recursive($config, $local);
}

return $config;
