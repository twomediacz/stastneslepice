<?php

/**
 * Endpoint receiving temperature and humidity readings.
 * The Raspberry Pi in the coop (script coop-climate) posts here every 5 minutes.
 *
 * Authenticated by the X-Raspberry-Token header, which must match 'raspberry_key'
 * in the config (the same token the Pi uses to download raspberry-config.php).
 *
 * Expected data (POST, JSON or form-data):
 *   temperature_coop    - temperature in the coop (°C, -40 to 80)
 *   humidity_coop       - humidity in the coop (%, 0 to 100)
 *   temperature_outdoor - temperature in the outdoor run (°C, -40 to 80)
 *   humidity_outdoor    - humidity in the outdoor run (%, 0 to 100)
 *
 * Temperature and humidity of one place are sent together (they are stored as one record).
 * A place whose sensor did not respond is simply left out.
 *
 * Example:
 *   curl -X POST https://slepice.twomedia.cz/input.php \
 *     -H "X-Raspberry-Token: secret-token" -H "Content-Type: application/json" \
 *     -d '{"temperature_coop":22.5,"humidity_coop":65,"temperature_outdoor":18.3,"humidity_outdoor":72}'
 */

// Bootstrap
require_once __DIR__ . '/../src/Core/autoload.php';

$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

use App\Core\Database;
use App\Models\ClimateRecord;

// Load configuration
$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);
Database::connect($config['db']);

// Helpers
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

/**
 * Numeric value from the request. Missing or empty = null.
 * Non-numeric or out of range = error 400 (previously it silently became 0).
 */
function readNumber(array $data, string $key, float $min, float $max): ?float
{
    if (!isset($data[$key]) || $data[$key] === '') {
        return null;
    }
    if (!is_numeric($data[$key])) {
        jsonError("Value $key is not a number.");
    }
    $value = (float) $data[$key];
    if ($value < $min || $value > $max) {
        jsonError("Value $key is out of range $min to $max.");
    }
    return $value;
}

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Only the POST method is allowed.', 405);
}

// Token check
$token = $_SERVER['HTTP_X_RASPBERRY_TOKEN'] ?? '';
$expectedToken = (string) ($config['api']['raspberry_key'] ?? '');

if (strlen($expectedToken) < 16 || !hash_equals($expectedToken, $token)) {
    jsonError('Invalid token.', 401);
}

// Read data
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}
if (!is_array($data)) {
    $data = [];
}

// Parse values (ranges match the DHT22 sensor)
$places = [
    'coop'    => ['name' => 'the coop',
                  'temperature' => readNumber($data, 'temperature_coop', -40, 80),
                  'humidity'    => readNumber($data, 'humidity_coop', 0, 100)],
    'outdoor' => ['name' => 'the outdoor run',
                  'temperature' => readNumber($data, 'temperature_outdoor', -40, 80),
                  'humidity'    => readNumber($data, 'humidity_outdoor', 0, 100)],
];

// Temperature and humidity of one place go into one record, so both or nothing.
// (Previously the missing one was stored as 0, which looked like a real reading in charts.)
foreach ($places as $place) {
    if (($place['temperature'] === null) !== ($place['humidity'] === null)) {
        jsonError("Send both temperature and humidity for {$place['name']}.");
    }
}

$saved = [];
foreach ($places as $location => $place) {
    if ($place['temperature'] === null) {
        continue;
    }
    $id = ClimateRecord::add($location, $place['temperature'], $place['humidity']);
    $saved[$location] = ['id' => $id, 'temperature' => $place['temperature'], 'humidity' => $place['humidity']];
}

if ($saved === []) {
    jsonError('No data received. Send temperature and humidity for the coop or the outdoor run.');
}

jsonResponse(['success' => true, 'saved' => $saved]);
