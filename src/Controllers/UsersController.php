<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;

class UsersController
{
    public function index(): void
    {
        Auth::requireAuth();

        $users = User::getAll();

        View::render('users', [
            'title' => 'Správa uživatelů',
            'users' => $users,
        ]);
    }
}
