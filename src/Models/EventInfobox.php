<?php

namespace App\Models;

class EventInfobox extends Model
{
    protected static string $table = 'event_infoboxes';

    public static function findByEvent(int $eventId): array
    {
        return static::query(
            "SELECT * FROM event_infoboxes WHERE event_id = ? ORDER BY sort_order ASC, id ASC",
            [$eventId]
        );
    }

    public static function getMaxSortOrder(int $eventId): int
    {
        return (int) static::queryValue(
            "SELECT COALESCE(MAX(sort_order), 0) FROM event_infoboxes WHERE event_id = ?",
            [$eventId]
        );
    }

    public static function reorder(array $ids): void
    {
        foreach ($ids as $index => $id) {
            static::update((int) $id, ['sort_order' => $index]);
        }
    }
}
