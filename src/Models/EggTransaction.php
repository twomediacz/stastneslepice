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
        return static::query(
            "SELECT * FROM egg_transactions
             WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             ORDER BY transaction_date DESC, id DESC",
            [$days]
        );
    }

    public static function getTotalRevenue(int $months = 12): float
    {
        return (float) static::queryValue(
            "SELECT COALESCE(SUM(price_total), 0)
             FROM egg_transactions
             WHERE type = 'sale'
               AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)",
            [$months]
        );
    }

    public static function getTotalSold(int $months = 12): int
    {
        return (int) static::queryValue(
            "SELECT COALESCE(SUM(quantity), 0)
             FROM egg_transactions
             WHERE type = 'sale'
               AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)",
            [$months]
        );
    }

    public static function getTotalGifted(int $months = 12): int
    {
        return (int) static::queryValue(
            "SELECT COALESCE(SUM(quantity), 0)
             FROM egg_transactions
             WHERE type = 'gift'
               AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)",
            [$months]
        );
    }

    public static function getMonthlyRevenue(int $months = 12): array
    {
        return static::query(
            "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS month,
                    SUM(CASE WHEN type = 'sale' THEN price_total ELSE 0 END) AS revenue,
                    SUM(CASE WHEN type = 'sale' THEN quantity ELSE 0 END) AS sold,
                    SUM(CASE WHEN type = 'gift' THEN quantity ELSE 0 END) AS gifted
             FROM egg_transactions
             WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
             ORDER BY month ASC",
            [$months]
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
