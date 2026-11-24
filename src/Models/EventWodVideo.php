<?php

namespace App\Models;

class EventWodVideo extends Model
{
    protected static string $table = 'event_wod_videos';

    public static function findByWod(int $wodId): array
    {
        return static::query(
            "SELECT * FROM event_wod_videos WHERE wod_id = ? ORDER BY sort_order ASC, id ASC",
            [$wodId]
        );
    }

    public static function getMaxSortOrder(int $wodId): int
    {
        return (int) static::queryValue(
            "SELECT COALESCE(MAX(sort_order), 0) FROM event_wod_videos WHERE wod_id = ?",
            [$wodId]
        );
    }

    public static function reorder(array $ids): void
    {
        foreach ($ids as $index => $id) {
            static::update((int) $id, ['sort_order' => $index]);
        }
    }
}
