<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;
use App\Models\Setting;

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
}
