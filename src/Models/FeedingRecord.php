<?php

namespace App\Models;

class FeedingRecord extends Model
{
    protected static string $table = 'feeding_records';

    public static function getRecent(int $days = 30): array
    {
        [$condition, $params] = static::dateRangeCondition('fr.record_date', $days, 'day');

        return static::query(
            "SELECT fr.*, ft.name AS feed_type_name, ft.price_per_kg
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE {$condition}
             ORDER BY fr.record_date DESC, fr.id DESC",
            $params
        );
    }

    public static function getDailyConsumption(int $days = 30): array
    {
        [$condition, $params] = static::dateRangeCondition('fr.record_date', $days, 'day');

        return static::query(
            "SELECT fr.record_date,
                    SUM(fr.amount_kg) AS total_kg,
                    SUM(fr.amount_kg * ft.price_per_kg) AS total_cost
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE {$condition}
             GROUP BY fr.record_date
             ORDER BY fr.record_date ASC",
            $params
        );
    }

    public static function getWeeklyConsumption(int $weeks = 12): array
    {
        [$condition, $params] = static::dateRangeCondition('fr.record_date', $weeks, 'week');
        $weekBucket = static::weekBucket('fr.record_date');

        return static::query(
            "SELECT {$weekBucket} AS yw,
                    MIN(fr.record_date) AS week_start,
                    SUM(fr.amount_kg) AS total_kg,
                    SUM(fr.amount_kg * ft.price_per_kg) AS total_cost
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE {$condition}
             GROUP BY {$weekBucket}
             ORDER BY yw ASC",
            $params
        );
    }

    public static function getMonthlyConsumption(int $months = 12): array
    {
        [$condition, $params] = static::dateRangeCondition('fr.record_date', $months, 'month');
        $monthBucket = static::monthBucket('fr.record_date');

        return static::query(
            "SELECT {$monthBucket} AS month,
                    SUM(fr.amount_kg) AS total_kg,
                    SUM(fr.amount_kg * ft.price_per_kg) AS total_cost
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE {$condition}
             GROUP BY {$monthBucket}
             ORDER BY month ASC",
            $params
        );
    }

    public static function getConsumptionByType(int $days = 30): array
    {
        [$condition, $params] = static::dateRangeCondition('fr.record_date', $days, 'day');

        return static::query(
            "SELECT ft.id, ft.name,
                    SUM(fr.amount_kg) AS total_kg,
                    SUM(fr.amount_kg * ft.price_per_kg) AS total_cost
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE {$condition}
             GROUP BY ft.id, ft.name
             ORDER BY total_kg DESC",
            $params
        );
    }

    public static function getDailyConsumptionByType(int $days = 30): array
    {
        [$condition, $params] = static::dateRangeCondition('fr.record_date', $days, 'day');

        return static::query(
            "SELECT fr.record_date, ft.id AS feed_type_id, ft.name AS feed_type_name,
                    SUM(fr.amount_kg) AS total_kg
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE {$condition}
             GROUP BY fr.record_date, ft.id, ft.name
             ORDER BY fr.record_date ASC",
            $params
        );
    }

    public static function getMonthlyConsumptionByType(int $months = 12): array
    {
        [$condition, $params] = static::dateRangeCondition('fr.record_date', $months, 'month');
        $monthBucket = static::monthBucket('fr.record_date');

        return static::query(
            "SELECT {$monthBucket} AS month,
                    ft.id AS feed_type_id, ft.name AS feed_type_name,
                    SUM(fr.amount_kg) AS total_kg
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE {$condition}
             GROUP BY {$monthBucket}, ft.id, ft.name
             ORDER BY month ASC",
            $params
        );
    }

    public static function getTotalKg(int $days = 30): float
    {
        [$condition, $params] = static::dateRangeCondition('record_date', $days, 'day');

        return (float) static::queryValue(
            "SELECT COALESCE(SUM(amount_kg), 0)
             FROM feeding_records
             WHERE {$condition}",
            $params
        );
    }

    public static function getTotalCost(int $days = 30): float
    {
        [$condition, $params] = static::dateRangeCondition('fr.record_date', $days, 'day');

        return (float) static::queryValue(
            "SELECT COALESCE(SUM(fr.amount_kg * ft.price_per_kg), 0)
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE {$condition}",
            $params
        );
    }

    public static function getDailyAverage(int $days = 30): float
    {
        [$condition, $params] = static::dateRangeCondition('record_date', $days, 'day');

        $result = static::queryOne(
            "SELECT COALESCE(SUM(amount_kg), 0) AS total,
                    COUNT(DISTINCT record_date) AS days_count
             FROM feeding_records
             WHERE {$condition}",
            $params
        );
        $daysCount = (int) $result['days_count'];
        return $daysCount > 0 ? round((float) $result['total'] / $daysCount, 2) : 0;
    }
}
