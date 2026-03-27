<?php

namespace App\Models;

class BeddingChange extends Model
{
    protected static string $table = 'bedding_changes';

    public static function getAll(): array
    {
        return static::query(
            "SELECT * FROM bedding_changes ORDER BY changed_at DESC"
        );
    }

    public static function getLatest(): ?array
    {
        return static::queryOne(
            "SELECT * FROM bedding_changes ORDER BY changed_at DESC LIMIT 1"
        );
    }
}
