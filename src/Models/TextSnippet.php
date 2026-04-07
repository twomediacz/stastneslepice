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

    public static function getDaily(string $type, ?string $date = null): ?array
    {
        $count = (int) static::queryValue(
            'SELECT COUNT(*) FROM text_snippets WHERE type = ?',
            [$type]
        );

        if ($count === 0) {
            return null;
        }

        $date ??= date('Y-m-d');
        $offset = (int) (abs(crc32($type . '|' . $date)) % $count);

        return static::queryOne(
            'SELECT * FROM text_snippets WHERE type = ? ORDER BY id ASC LIMIT 1 OFFSET ?',
            [$type, $offset]
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
