<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\TestAbortException;
use App\Core\View;
use App\Models\User;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        View::render('login', ['title' => 'Chov slepic – Doloplazy – Přihlášení']);
    }

    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            View::render('login', [
                'title' => 'Chov slepic – Doloplazy – Přihlášení',
                'error' => 'Vyplňte uživatelské jméno a heslo.',
                'username' => $username,
            ]);
            return;
        }

        $user = User::findByUsername($username);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            View::render('login', [
                'title' => 'Chov slepic – Doloplazy – Přihlášení',
                'error' => 'Nesprávné přihlašovací údaje.',
                'username' => $username,
            ]);
            return;
        }

        Auth::login($user['id'], $user['username'], $user['role']);
        $this->redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/');
    }

    private function redirect(string $location): void
    {
        header("Location: {$location}", true, 302);

        if (defined('APP_TEST_MODE') && APP_TEST_MODE) {
            throw new TestAbortException("Redirected to {$location}");
        }

        exit;
    }
}
