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

    public function showRegister(): void
    {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }
        View::render('register', ['title' => 'Registrace']);
    }

    public function register(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $error = null;
        if ($username === '' || $password === '') {
            $error = 'Vyplňte všechna pole.';
        } elseif (strlen($password) < 6) {
            $error = 'Heslo musí mít alespoň 6 znaků.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Hesla se neshodují.';
        } elseif (User::findByUsername($username)) {
            $error = 'Uživatelské jméno je již obsazené.';
        }

        if ($error) {
            View::render('register', [
                'title' => 'Registrace',
                'error' => $error,
                'username' => $username,
            ]);
            return;
        }

        $userId = User::create($username, $password);
        Auth::login($userId, $username, 'admin');
        header('Location: /');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /login');
        exit;
    }
}
