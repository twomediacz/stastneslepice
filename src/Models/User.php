<?php

namespace App\Models;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByUsername(string $username): ?array
    {
        return static::queryOne(
            "SELECT * FROM users WHERE username = ?",
            [$username]
        );
    }

    public static function getAll(): array
    {
        return static::query("SELECT id, username, role, created_at FROM users ORDER BY id");
    }

    public static function create(string $username, string $password, string $role = 'admin'): int
    {
        return static::insert([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
        ]);
    }

    public static function updatePassword(int $id, string $password): bool
    {
        return static::update($id, [
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
    }
}
