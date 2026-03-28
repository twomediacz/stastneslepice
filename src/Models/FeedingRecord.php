<?php

namespace App\Models;

class FeedingRecord extends Model
{
    protected static string $table = 'feeding_records';

    public static function getRecent(int $days = 30): array
    {
        return static::query(
            "SELECT fr.*, ft.name AS feed_type_name, ft.price_per_kg
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE fr.record_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             ORDER BY fr.record_date DESC, fr.id DESC",
            [$days]
        );
    }

    public static function getDailyConsumption(int $days = 30): array
    {
        return static::query(
            "SELECT fr.record_date,
                    SUM(fr.amount_kg) AS total_kg,
                    SUM(fr.amount_kg * ft.price_per_kg) AS total_cost
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE fr.record_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY fr.record_date
             ORDER BY fr.record_date ASC",
            [$days]
        );
    }

    public static function getWeeklyConsumption(int $weeks = 12): array
    {
        return static::query(
            "SELECT YEARWEEK(fr.record_date, 1) AS yw,
                    MIN(fr.record_date) AS week_start,
                    SUM(fr.amount_kg) AS total_kg,
                    SUM(fr.amount_kg * ft.price_per_kg) AS total_cost
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE fr.record_date >= DATE_SUB(CURDATE(), INTERVAL ? WEEK)
             GROUP BY YEARWEEK(fr.record_date, 1)
             ORDER BY yw ASC",
            [$weeks]
        );
    }

    public static function getMonthlyConsumption(int $months = 12): array
    {
        return static::query(
            "SELECT DATE_FORMAT(fr.record_date, '%Y-%m') AS month,
                    SUM(fr.amount_kg) AS total_kg,
                    SUM(fr.amount_kg * ft.price_per_kg) AS total_cost
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE fr.record_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY DATE_FORMAT(fr.record_date, '%Y-%m')
             ORDER BY month ASC",
            [$months]
        );
    }

    public static function getConsumptionByType(int $days = 30): array
    {
        return static::query(
            "SELECT ft.id, ft.name,
                    SUM(fr.amount_kg) AS total_kg,
                    SUM(fr.amount_kg * ft.price_per_kg) AS total_cost
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE fr.record_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY ft.id, ft.name
             ORDER BY total_kg DESC",
            [$days]
        );
    }

    public static function getDailyConsumptionByType(int $days = 30): array
    {
        return static::query(
            "SELECT fr.record_date, ft.id AS feed_type_id, ft.name AS feed_type_name,
                    SUM(fr.amount_kg) AS total_kg
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE fr.record_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY fr.record_date, ft.id, ft.name
             ORDER BY fr.record_date ASC",
            [$days]
        );
    }

    public static function getMonthlyConsumptionByType(int $months = 12): array
    {
        return static::query(
            "SELECT DATE_FORMAT(fr.record_date, '%Y-%m') AS month,
                    ft.id AS feed_type_id, ft.name AS feed_type_name,
                    SUM(fr.amount_kg) AS total_kg
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE fr.record_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY DATE_FORMAT(fr.record_date, '%Y-%m'), ft.id, ft.name
             ORDER BY month ASC",
            [$months]
        );
    }

    public static function getTotalKg(int $days = 30): float
    {
        return (float) static::queryValue(
            "SELECT COALESCE(SUM(amount_kg), 0)
             FROM feeding_records
             WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            [$days]
        );
    }

    public static function getTotalCost(int $days = 30): float
    {
        return (float) static::queryValue(
            "SELECT COALESCE(SUM(fr.amount_kg * ft.price_per_kg), 0)
             FROM feeding_records fr
             JOIN feed_types ft ON fr.feed_type_id = ft.id
             WHERE fr.record_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            [$days]
        );
    }

    public static function getDailyAverage(int $days = 30): float
    {
        $result = static::queryOne(
            "SELECT COALESCE(SUM(amount_kg), 0) AS total,
                    COUNT(DISTINCT record_date) AS days_count
             FROM feeding_records
             WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            [$days]
        );
        $daysCount = (int) $result['days_count'];
        return $daysCount > 0 ? round((float) $result['total'] / $daysCount, 2) : 0;
    }
}
