<?php

namespace App\Models;

class ClimateRecord extends Model
{
    protected static string $table = 'climate_records';

    public static function getLatest(string $location): ?array
    {
        return static::queryOne(
            "SELECT * FROM climate_records WHERE location = ? ORDER BY recorded_at DESC LIMIT 1",
            [$location]
        );
    }

    public static function getHistory(string $location, int $hours = 24): array
    {
        return static::query(
            "SELECT * FROM climate_records
             WHERE location = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
             ORDER BY recorded_at ASC",
            [$location, $hours]
        );
    }

    public static function getDailyMinMax(string $location, int $days = 7): array
    {
        return static::query(
            "SELECT DATE(recorded_at) AS date,
                    MIN(temperature) AS temp_min, MAX(temperature) AS temp_max,
                    MIN(humidity) AS hum_min, MAX(humidity) AS hum_max
             FROM climate_records
             WHERE location = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(recorded_at)
             ORDER BY date ASC",
            [$location, $days]
        );
    }

    public static function getMonthlyMinMax(string $location, int $months = 12): array
    {
        return static::query(
            "SELECT DATE_FORMAT(recorded_at, '%Y-%m') AS month,
                    MIN(temperature) AS temp_min, MAX(temperature) AS temp_max,
                    MIN(humidity) AS hum_min, MAX(humidity) AS hum_max
             FROM climate_records
             WHERE location = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
             GROUP BY DATE_FORMAT(recorded_at, '%Y-%m')
             ORDER BY month ASC",
            [$location, $months]
        );
    }

    public static function add(string $location, float $temperature, float $humidity): int
    {
        return static::insert([
            'recorded_at' => date('Y-m-d H:i:s'),
            'location' => $location,
            'temperature' => $temperature,
            'humidity' => $humidity,
        ]);
    }
}
