<?php

namespace App\Models;

class Setting extends Model
{
    protected static string $table = 'settings';

    public static function get(string $key): ?string
    {
        $row = static::queryOne(
            "SELECT setting_value FROM settings WHERE setting_key = ?",
            [$key]
        );
        return $row ? $row['setting_value'] : null;
    }

    public static function set(string $key, string $value): void
    {
        $stmt = static::db()->prepare(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $stmt->execute([$key, $value]);
    }

    public static function getAll(): array
    {
        $rows = static::query("SELECT setting_key, setting_value FROM settings");
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }

}
