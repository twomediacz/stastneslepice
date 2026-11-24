<?php

namespace App\Models;

class EventWodImage extends Model
{
    protected static string $table = 'event_wod_images';

    public static function findByWod(int $wodId): array
    {
        return static::query(
            "SELECT * FROM event_wod_images WHERE wod_id = ? ORDER BY sort_order ASC, id ASC",
            [$wodId]
        );
    }

    public static function getMaxSortOrder(int $wodId): int
    {
        return (int) static::queryValue(
            "SELECT COALESCE(MAX(sort_order), 0) FROM event_wod_images WHERE wod_id = ?",
            [$wodId]
        );
    }

    public static function deleteImage(int $id): bool
    {
        $image = static::findById($id);
        if (!$image) return false;

        $uploadDir = __DIR__ . '/../../public/uploads/';
        $thumbDir = $uploadDir . 'thumbs/';

        $filePath = $uploadDir . $image['filename'];
        $thumbPath = $thumbDir . $image['filename'];

        if (file_exists($filePath)) unlink($filePath);
        if (file_exists($thumbPath)) unlink($thumbPath);

        return static::delete($id);
    }

    public static function deleteByWod(int $wodId): void
    {
        $images = static::findByWod($wodId);
        foreach ($images as $image) {
            static::deleteImage($image['id']);
        }
    }

    public static function reorder(array $ids): void
    {
        foreach ($ids as $index => $id) {
            static::update((int) $id, ['sort_order' => $index]);
        }
    }
}
