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
        [$condition, $params] = static::dateRangeCondition('record_date', $days, 'day');

        return static::query(
            "SELECT * FROM egg_records WHERE {$condition} ORDER BY record_date DESC",
            $params
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

    public static function getMonthlyAggregated(int $months = 12): array
    {
        [$condition, $params] = static::dateRangeCondition('record_date', $months, 'month');
        $monthBucket = static::monthBucket('record_date');

        return static::query(
            "SELECT {$monthBucket} AS month,
                    SUM(egg_count) AS egg_count
             FROM egg_records
             WHERE {$condition}
             GROUP BY {$monthBucket}
             ORDER BY month ASC",
            $params
        );
    }

    public static function upsert(string $date, int $count, ?string $note): void
    {
        $sql = static::isSqlite()
            ? "INSERT INTO egg_records (record_date, egg_count, note) VALUES (?, ?, ?)
               ON CONFLICT(record_date) DO UPDATE SET egg_count = excluded.egg_count, note = excluded.note"
            : "INSERT INTO egg_records (record_date, egg_count, note) VALUES (?, ?, ?)
               ON DUPLICATE KEY UPDATE egg_count = VALUES(egg_count), note = VALUES(note)";
        $stmt = static::db()->prepare($sql);
        $stmt->execute([$date, $count, $note]);
    }
}
