<?php

namespace App\Models;

class Chicken extends Model
{
    protected static string $table = 'chickens';

    public static function getAll(): array
    {
        return static::query("SELECT * FROM chickens ORDER BY status ASC, name ASC");
    }

    public static function getActive(): array
    {
        return static::query(
            "SELECT * FROM chickens WHERE status = 'active' ORDER BY name ASC"
        );
    }

    public static function getCount(): array
    {
        return [
            'total' => (int) static::queryValue("SELECT COUNT(*) FROM chickens"),
            'active' => (int) static::queryValue("SELECT COUNT(*) FROM chickens WHERE status = 'active'"),
            'sick' => (int) static::queryValue("SELECT COUNT(*) FROM chickens WHERE status = 'sick'"),
        ];
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Aktivní',
            'sick' => 'Nemocná',
            'deceased' => 'Uhynulá',
            'given_away' => 'Darovaná',
            default => $status,
        };
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'active' => '#5a9a2a',
            'sick' => '#e8a020',
            'deceased' => '#999',
            'given_away' => '#7a8fa5',
            default => '#777',
        };
    }
}
