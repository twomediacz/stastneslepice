<?php

/**
 * Endpoint pro příjem teplot a vlhkostí z externího systému (IoT senzory apod.).
 *
 * Zabezpečeno API klíčem (parametr "api_key").
 *
 * Očekávaná data (POST, JSON nebo form-data):
 *   api_key            - povinný, musí odpovídat hodnotě v configu
 *   temperature_coop   - teplota v kurníku (°C)
 *   humidity_coop      - vlhkost v kurníku (%)
 *   temperature_outdoor - teplota ve výběhu (°C)
 *   humidity_outdoor   - vlhkost ve výběhu (%)
 *
 * Příklad:
 *   curl -X POST https://example.com/prijem.php \
 *     -H "Content-Type: application/json" \
 *     -d '{"api_key":"tajny-klic","temperature_coop":22.5,"humidity_coop":65,"temperature_outdoor":18.3,"humidity_outdoor":72}'
 */

// Bootstrap
require_once __DIR__ . '/../src/Core/autoload.php';

$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

use App\Core\Database;
use App\Models\ClimateRecord;

// Načtení konfigurace
$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);
Database::connect($config['db']);

// Pomocné funkce
function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $status = 400): void
{
    jsonResponse(['error' => $message], $status);
}

// Pouze POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Povolena pouze metoda POST.', 405);
}

// Načtení dat
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
} else {
    $data = $_POST;
}

// Ověření API klíče
$apiKey = $data['api_key'] ?? '';
$expectedKey = $config['api']['climate_key'] ?? '';

if ($expectedKey === '' || !hash_equals($expectedKey, (string) $apiKey)) {
    jsonError('Neplatný API klíč.', 401);
}

// Parsování hodnot
$tempCoop = isset($data['temperature_coop']) ? (float) $data['temperature_coop'] : null;
$humCoop = isset($data['humidity_coop']) ? (float) $data['humidity_coop'] : null;
$tempOutdoor = isset($data['temperature_outdoor']) ? (float) $data['temperature_outdoor'] : null;
$humOutdoor = isset($data['humidity_outdoor']) ? (float) $data['humidity_outdoor'] : null;

// Alespoň jedna hodnota musí být zadána
if ($tempCoop === null && $humCoop === null && $tempOutdoor === null && $humOutdoor === null) {
    jsonError('Nebyla zadána žádná data. Pošlete alespoň jednu hodnotu teploty nebo vlhkosti.');
}

$saved = [];

// Uložení dat kurníku
if ($tempCoop !== null || $humCoop !== null) {
    $id = ClimateRecord::add('coop', $tempCoop ?? 0, $humCoop ?? 0);
    $saved['coop'] = ['id' => $id, 'temperature' => $tempCoop, 'humidity' => $humCoop];
}

// Uložení dat výběhu
if ($tempOutdoor !== null || $humOutdoor !== null) {
    $id = ClimateRecord::add('outdoor', $tempOutdoor ?? 0, $humOutdoor ?? 0);
    $saved['outdoor'] = ['id' => $id, 'temperature' => $tempOutdoor, 'humidity' => $humOutdoor];
}

jsonResponse(['success' => true, 'saved' => $saved]);
