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

    public static function create(string $username, string $password, string $role = 'admin'): int
    {
        return static::insert([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
        ]);
    }
}
