<?php

namespace App\Models;

class EventWod extends Model
{
    protected static string $table = 'event_wods';

    public static function findByEvent(int $eventId): array
    {
        return static::query(
            "SELECT * FROM event_wods WHERE event_id = ? ORDER BY sort_order ASC, id ASC",
            [$eventId]
        );
    }

    public static function findByIdWithMedia(int $id): ?array
    {
        $wod = static::findById($id);
        if (!$wod) return null;

        $wod['videos'] = EventWodVideo::findByWod($id);
        $wod['images'] = EventWodImage::findByWod($id);

        return $wod;
    }

    public static function getMaxSortOrder(int $eventId): int
    {
        return (int) static::queryValue(
            "SELECT COALESCE(MAX(sort_order), 0) FROM event_wods WHERE event_id = ?",
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
