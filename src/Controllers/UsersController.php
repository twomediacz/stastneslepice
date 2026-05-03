<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\User;
use App\Models\Setting;
use App\Services\ExistingPhotoResizeService;

class UsersController
{
    public function index(): void
    {
        Auth::requireAuth();

        $users = User::getAll();
        $settings = Setting::getAll();

        View::render('users', [
            'title' => 'Chov slepic – Doloplazy – Nastavení',
            'users' => $users,
            'settings' => $settings,
        ]);
    }

    public function resizePhotos(): void
    {
        Auth::requireAuth();

        $isRun = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
            && ($_POST['confirm'] ?? '') === '1';
        $max = (int) ($_POST['max'] ?? $_GET['max'] ?? 1080);
        $backup = ($_POST['backup'] ?? $_GET['backup'] ?? '1') !== '0';

        $result = (new ExistingPhotoResizeService())->run(
            Database::getInstance(),
            dirname(__DIR__, 2),
            [
                'dry_run' => !$isRun,
                'backup' => $backup,
                'max' => $max,
            ]
        );

        View::render('resize_photos', [
            'title' => 'Chov slepic – Doloplazy – Zmenšení fotek',
            'result' => $result,
            'isRun' => $isRun,
            'max' => $max,
            'backup' => $backup,
        ]);
    }
}
