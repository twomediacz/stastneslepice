<?php

namespace App\Models;

class EggRecord extends Model
{
    protected static string $table = 'egg_records';

    public static function getByDate(string $date): ?array
    {
        return static::queryOne(
            "SELECT * FROM egg_records WHERE record_date = ?",
            [$date]
        );
    }

    public static function getRecent(int $days = 14): array
    {
        return static::query(
            "SELECT * FROM egg_records WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) ORDER BY record_date DESC",
            [$days]
        );
    }

    public static function getTotalEggs(): int
    {
        return (int) static::queryValue("SELECT COALESCE(SUM(egg_count), 0) FROM egg_records");
    }

    public static function getDailyAverage(): float
    {
        $result = static::queryOne(
            "SELECT COALESCE(AVG(egg_count), 0) AS avg_eggs, COUNT(*) AS days FROM egg_records"
        );
        return round((float) $result['avg_eggs'], 1);
    }

    public static function upsert(string $date, int $count, ?string $note): void
    {
        $stmt = static::db()->prepare(
            "INSERT INTO egg_records (record_date, egg_count, note) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE egg_count = VALUES(egg_count), note = VALUES(note)"
        );
        $stmt->execute([$date, $count, $note]);
    }
}
