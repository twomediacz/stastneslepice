<?php

namespace App\Models;

class Event extends Model
{
    protected static string $table = 'events';

    private const STATUS_LABELS = [
        'draft' => 'Koncept',
        'registration_open' => 'Registrace otevřena',
        'registration_closed' => 'Registrace uzavřena',
        'in_progress' => 'Probíhá',
        'finished' => 'Ukončeno',
    ];

    private const TYPE_LABELS = [
        'qualification' => 'Kvalifikace',
        'race' => 'Závod',
    ];

    public static function getAll(?string $type = null, ?string $status = null): array
    {
        $sql = "SELECT * FROM events WHERE 1=1";
        $params = [];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY event_start DESC, created_at DESC";
        return static::query($sql, $params);
    }

    public static function findByIdWithDetails(int $id): ?array
    {
        $event = static::findById($id);
        if (!$event) return null;

        $event['infoboxes'] = EventInfobox::findByEvent($id);
        $event['categories'] = EventCategory::findByEvent($id);

        $wods = EventWod::findByEvent($id);
        foreach ($wods as &$wod) {
            $wod['videos'] = EventWodVideo::findByWod($wod['id']);
            $wod['images'] = EventWodImage::findByWod($wod['id']);
        }
        $event['wods'] = $wods;

        return $event;
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? $type;
    }

    public static function validTypes(): array
    {
        return array_keys(self::TYPE_LABELS);
    }

    public static function validStatuses(): array
    {
        return array_keys(self::STATUS_LABELS);
    }
}
