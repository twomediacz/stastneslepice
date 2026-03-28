<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Models\User;

class UserController extends Controller
{
    public function index(): void
    {
        Auth::requireAuthApi();
        $users = User::getAll();
        $this->json(['users' => $users]);
    }

    public function store(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'admin';

        if ($username === '') {
            $this->jsonError('Uživatelské jméno je povinné.');
        }
        if (strlen($password) < 6) {
            $this->jsonError('Heslo musí mít alespoň 6 znaků.');
        }
        if (User::findByUsername($username)) {
            $this->jsonError('Uživatelské jméno je již obsazené.');
        }

        $id = User::create($username, $password, $role);
        $user = User::findById($id);

        $this->json([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'created_at' => $user['created_at'] ?? null,
            ],
        ]);
    }

    public function update(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        $user = User::findById($id);
        if (!$user) {
            $this->jsonError('Uživatel nenalezen.');
        }

        $username = trim($data['username'] ?? '');
        if ($username === '') {
            $this->jsonError('Uživatelské jméno je povinné.');
        }

        // Check uniqueness if username changed
        if ($username !== $user['username'] && User::findByUsername($username)) {
            $this->jsonError('Uživatelské jméno je již obsazené.');
        }

        $updateData = [
            'username' => $username,
            'role' => $data['role'] ?? $user['role'],
        ];
        User::update($id, $updateData);

        // Update password if provided
        $password = $data['password'] ?? '';
        if ($password !== '') {
            if (strlen($password) < 6) {
                $this->jsonError('Heslo musí mít alespoň 6 znaků.');
            }
            User::updatePassword($id, $password);
        }

        $updated = User::findById($id);
        $this->json([
            'success' => true,
            'user' => [
                'id' => $updated['id'],
                'username' => $updated['username'],
                'role' => $updated['role'],
                'created_at' => $updated['created_at'] ?? null,
            ],
        ]);
    }

    public function destroy(): void
    {
        Auth::requireAuthApi();
        $data = $this->getPostData();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Neplatné ID.');
        }

        // Prevent deleting yourself
        $currentUser = Auth::user();
        if ($currentUser && $currentUser['id'] == $id) {
            $this->jsonError('Nemůžete smazat sami sebe.');
        }

        User::delete($id);
        $this->json(['success' => true]);
    }
}
