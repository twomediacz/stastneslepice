<?php

namespace App\Models;

class EggTransaction extends Model
{
    protected static string $table = 'egg_transactions';

    public static function getAll(): array
    {
        return static::query(
            "SELECT * FROM egg_transactions ORDER BY transaction_date DESC, id DESC"
        );
    }

    public static function getRecent(int $days = 90): array
    {
        [$condition, $params] = static::dateRangeCondition('transaction_date', $days, 'day');

        return static::query(
            "SELECT * FROM egg_transactions
             WHERE {$condition}
             ORDER BY transaction_date DESC, id DESC",
            $params
        );
    }

    public static function getTotalRevenue(int $months = 12): float
    {
        [$condition, $params] = static::dateRangeCondition('transaction_date', $months, 'month');

        return (float) static::queryValue(
            "SELECT COALESCE(SUM(price_total), 0)
             FROM egg_transactions
             WHERE type = 'sale'
               AND {$condition}",
            $params
        );
    }

    public static function getTotalSold(int $months = 12): int
    {
        [$condition, $params] = static::dateRangeCondition('transaction_date', $months, 'month');

        return (int) static::queryValue(
            "SELECT COALESCE(SUM(quantity), 0)
             FROM egg_transactions
             WHERE type = 'sale'
               AND {$condition}",
            $params
        );
    }

    public static function getTotalGifted(int $months = 12): int
    {
        [$condition, $params] = static::dateRangeCondition('transaction_date', $months, 'month');

        return (int) static::queryValue(
            "SELECT COALESCE(SUM(quantity), 0)
             FROM egg_transactions
             WHERE type = 'gift'
               AND {$condition}",
            $params
        );
    }

    public static function getMonthlyRevenue(int $months = 12): array
    {
        [$condition, $params] = static::dateRangeCondition('transaction_date', $months, 'month');
        $monthBucket = static::monthBucket('transaction_date');

        return static::query(
            "SELECT {$monthBucket} AS month,
                    SUM(CASE WHEN type = 'sale' THEN price_total ELSE 0 END) AS revenue,
                    SUM(CASE WHEN type = 'sale' THEN quantity ELSE 0 END) AS sold,
                    SUM(CASE WHEN type = 'gift' THEN quantity ELSE 0 END) AS gifted
             FROM egg_transactions
             WHERE {$condition}
             GROUP BY {$monthBucket}
             ORDER BY month ASC",
            $params
        );
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'sale' => 'Prodej',
            'gift' => 'Darování',
            default => $type,
        };
    }
}
