<?php

namespace App\Models;

class TextSnippet extends Model
{
    protected static string $table = 'text_snippets';

    public static function getRandom(string $type): ?array
    {
        $orderBy = static::isSqlite() ? 'RANDOM()' : 'RAND()';

        return static::queryOne(
            "SELECT * FROM text_snippets WHERE type = ? ORDER BY {$orderBy} LIMIT 1",
            [$type]
        );
    }

    public static function getAllByType(string $type, int $limit = 100): array
    {
        return static::query(
            "SELECT * FROM text_snippets WHERE type = ? ORDER BY id DESC LIMIT ?",
            [$type, $limit]
        );
    }

    public static function add(string $type, string $content): int
    {
        return static::insert([
            'type' => $type,
            'content' => $content,
        ]);
    }
}
