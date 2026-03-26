<?php

// Composer autoloader (preferovaný) nebo vlastní fallback
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    require_once __DIR__ . '/../src/Core/autoload.php';
}

use App\Core\App;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\Api\EggController;
use App\Controllers\Api\ClimateController;
use App\Controllers\Api\NoteController;
use App\Controllers\Api\PhotoController;
use App\Controllers\Api\SettingController;
use App\Controllers\Api\WeatherController;
use App\Controllers\ChickensController;
use App\Controllers\LiveController;
use App\Controllers\Api\ChickenController;

$app = new App();
$router = $app->getRouter();

// Auth routy (veřejné)
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

// Stránky (vyžadují přihlášení – řeší controllery)
$router->get('/', [HomeController::class, 'index']);
$router->get('/slepice', [ChickensController::class, 'index']);
$router->get('/zive', [LiveController::class, 'index']);

// API – vejce
$router->get('/api/eggs', [EggController::class, 'index']);
$router->post('/api/eggs', [EggController::class, 'store']);
$router->post('/api/eggs/delete', [EggController::class, 'destroy']);

// API – klima
$router->get('/api/climate/latest', [ClimateController::class, 'latest']);
$router->get('/api/climate/history', [ClimateController::class, 'history']);
$router->post('/api/climate', [ClimateController::class, 'store']);

// API – poznámky
$router->get('/api/notes', [NoteController::class, 'index']);
$router->post('/api/notes', [NoteController::class, 'store']);
$router->post('/api/notes/update', [NoteController::class, 'update']);
$router->post('/api/notes/delete', [NoteController::class, 'destroy']);

// API – fotky
$router->get('/api/photos', [PhotoController::class, 'index']);
$router->post('/api/photos', [PhotoController::class, 'store']);
$router->post('/api/photos/delete', [PhotoController::class, 'destroy']);

// API – nastavení
$router->get('/api/settings', [SettingController::class, 'index']);
$router->post('/api/settings', [SettingController::class, 'update']);

// API – počasí
$router->get('/api/weather', [WeatherController::class, 'forecast']);

// API – slepice
$router->get('/api/chickens', [ChickenController::class, 'index']);
$router->post('/api/chickens', [ChickenController::class, 'store']);
$router->post('/api/chickens/update', [ChickenController::class, 'update']);
$router->post('/api/chickens/photo', [ChickenController::class, 'uploadPhoto']);
$router->post('/api/chickens/delete', [ChickenController::class, 'destroy']);

$app->run();
