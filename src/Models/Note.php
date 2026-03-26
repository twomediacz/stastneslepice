<?php

namespace App\Models;

class Note extends Model
{
    protected static string $table = 'notes';

    public static function getRecent(int $limit = 20): array
    {
        return static::query(
            "SELECT * FROM notes ORDER BY note_date DESC, id DESC LIMIT ?",
            [$limit]
        );
    }

    public static function add(string $date, string $content): int
    {
        return static::insert([
            'note_date' => $date,
            'content' => $content,
        ]);
    }

    public static function deleteNote(int $id): bool
    {
        return static::delete($id);
    }
}
