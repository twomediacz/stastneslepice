<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }
        View::render('login', ['title' => 'Přihlášení']);
    }

    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            View::render('login', [
                'title' => 'Přihlášení',
                'error' => 'Vyplňte uživatelské jméno a heslo.',
                'username' => $username,
            ]);
            return;
        }

        $user = User::findByUsername($username);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            View::render('login', [
                'title' => 'Přihlášení',
                'error' => 'Nesprávné přihlašovací údaje.',
                'username' => $username,
            ]);
            return;
        }

        Auth::login($user['id'], $user['username'], $user['role']);
        header('Location: /');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /');
        exit;
    }
}
