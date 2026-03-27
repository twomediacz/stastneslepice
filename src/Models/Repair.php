<?php

namespace App\Models;

class Repair extends Model
{
    protected static string $table = 'repairs';

    public static function getAll(): array
    {
        return static::query(
            "SELECT * FROM repairs ORDER BY repaired_at DESC"
        );
    }
}
