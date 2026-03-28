<?php

namespace App\Models;

class FeedType extends Model
{
    protected static string $table = 'feed_types';

    public static function getAll(): array
    {
        return static::query(
            "SELECT * FROM feed_types ORDER BY is_active DESC, name ASC"
        );
    }

    public static function getActive(): array
    {
        return static::query(
            "SELECT * FROM feed_types WHERE is_active = 1 ORDER BY name ASC"
        );
    }

    public static function palatabilityLabel(?int $rating): string
    {
        return match ($rating) {
            1 => 'Nechutná',
            2 => 'Spíše ne',
            3 => 'Nevadí',
            4 => 'Chutná',
            5 => 'Oblíbené',
            default => 'Nehodnoceno',
        };
    }
}
