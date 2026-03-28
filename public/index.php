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
use App\Controllers\MaintenanceController as MaintenancePageController;
use App\Controllers\Api\MaintenanceController;
use App\Controllers\Api\ChickenController;
use App\Controllers\FeedingController as FeedingPageController;
use App\Controllers\Api\FeedingController;
use App\Controllers\FinanceController as FinancePageController;
use App\Controllers\Api\FinanceController;
use App\Controllers\UsersController;
use App\Controllers\Api\UserController;
use App\Controllers\AlmanachController;
use App\Controllers\Api\TextSnippetController;

$app = new App();
$router = $app->getRouter();

// Auth routy (veřejné)
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// Stránky (vyžadují přihlášení – řeší controllery)
$router->get('/', [HomeController::class, 'index']);
$router->get('/slepice', [ChickensController::class, 'index']);
$router->get('/udrzba', [MaintenancePageController::class, 'index']);
$router->get('/krmeni', [FeedingPageController::class, 'index']);
$router->get('/finance', [FinancePageController::class, 'index']);
$router->get('/zive', [LiveController::class, 'index']);
$router->get('/uzivatele', [UsersController::class, 'index']);
$router->get('/almanach', [AlmanachController::class, 'index']);
$router->get('/almanach/pokrocily', [AlmanachController::class, 'pokrocily']);

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

// API – údržba (podestýlka)
$router->get('/api/bedding', [MaintenanceController::class, 'beddingIndex']);
$router->post('/api/bedding', [MaintenanceController::class, 'beddingStore']);
$router->post('/api/bedding/update', [MaintenanceController::class, 'beddingUpdate']);
$router->post('/api/bedding/delete', [MaintenanceController::class, 'beddingDestroy']);
$router->post('/api/bedding/interval', [MaintenanceController::class, 'beddingInterval']);
$router->post('/api/bedding/quick-log', [MaintenanceController::class, 'beddingQuickLog']);

// API – údržba (opravy)
$router->get('/api/repairs', [MaintenanceController::class, 'repairIndex']);
$router->post('/api/repairs', [MaintenanceController::class, 'repairStore']);
$router->post('/api/repairs/update', [MaintenanceController::class, 'repairUpdate']);
$router->post('/api/repairs/delete', [MaintenanceController::class, 'repairDestroy']);

// API – krmení (typy krmiva)
$router->get('/api/feeding/types', [FeedingController::class, 'types']);
$router->post('/api/feeding/types', [FeedingController::class, 'storeType']);
$router->post('/api/feeding/types/update', [FeedingController::class, 'updateType']);
$router->post('/api/feeding/types/delete', [FeedingController::class, 'deleteType']);

// API – krmení (záznamy)
$router->get('/api/feeding/records', [FeedingController::class, 'records']);
$router->post('/api/feeding/records', [FeedingController::class, 'storeRecord']);
$router->post('/api/feeding/records/update', [FeedingController::class, 'updateRecord']);
$router->post('/api/feeding/records/delete', [FeedingController::class, 'deleteRecord']);

// API – krmení (nákupy)
$router->get('/api/feeding/purchases', [FeedingController::class, 'purchases']);
$router->post('/api/feeding/purchases', [FeedingController::class, 'storePurchase']);
$router->post('/api/feeding/purchases/delete', [FeedingController::class, 'deletePurchase']);

// API – krmení (statistiky)
$router->get('/api/feeding/stats', [FeedingController::class, 'stats']);

// API – finance (náklady)
$router->get('/api/finance/expenses', [FinanceController::class, 'expenses']);
$router->post('/api/finance/expenses', [FinanceController::class, 'storeExpense']);
$router->post('/api/finance/expenses/update', [FinanceController::class, 'updateExpense']);
$router->post('/api/finance/expenses/delete', [FinanceController::class, 'deleteExpense']);

// API – finance (prodej/darování vajec)
$router->get('/api/finance/egg-transactions', [FinanceController::class, 'eggTransactions']);
$router->post('/api/finance/egg-transactions', [FinanceController::class, 'storeEggTransaction']);
$router->post('/api/finance/egg-transactions/update', [FinanceController::class, 'updateEggTransaction']);
$router->post('/api/finance/egg-transactions/delete', [FinanceController::class, 'deleteEggTransaction']);

// API – finance (nastavení + souhrn)
$router->post('/api/finance/egg-market-price', [FinanceController::class, 'updateEggMarketPrice']);
$router->get('/api/finance/summary', [FinanceController::class, 'summary']);

// API – uživatelé
$router->get('/api/users', [UserController::class, 'index']);
$router->post('/api/users', [UserController::class, 'store']);
$router->post('/api/users/update', [UserController::class, 'update']);
$router->post('/api/users/delete', [UserController::class, 'destroy']);

// API – texty (vtipy, rady)
$router->get('/api/snippets/random', [TextSnippetController::class, 'random']);
$router->get('/api/snippets', [TextSnippetController::class, 'index']);
$router->post('/api/snippets', [TextSnippetController::class, 'store']);
$router->post('/api/snippets/update', [TextSnippetController::class, 'update']);
$router->post('/api/snippets/delete', [TextSnippetController::class, 'destroy']);

$app->run();
