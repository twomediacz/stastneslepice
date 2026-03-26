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

$app = new App();
$router = $app->getRouter();

// Registrace rout
$router->get('/', [HomeController::class, 'index']);

$app->run();
