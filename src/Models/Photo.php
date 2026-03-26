<?php

namespace App\Models;

class Photo extends Model
{
    protected static string $table = 'photos';

    public static function getAll(int $limit = 50): array
    {
        return static::query(
            "SELECT * FROM photos ORDER BY uploaded_at DESC LIMIT ?",
            [$limit]
        );
    }

    public static function add(string $filename, ?string $caption = null): int
    {
        return static::insert([
            'filename' => $filename,
            'caption' => $caption,
        ]);
    }

    public static function deletePhoto(int $id): bool
    {
        $photo = static::findById($id);
        if ($photo) {
            $basePath = __DIR__ . '/../../public/uploads/';
            @unlink($basePath . $photo['filename']);
            @unlink($basePath . 'thumbs/' . $photo['filename']);
            return static::delete($id);
        }
        return false;
    }
}
