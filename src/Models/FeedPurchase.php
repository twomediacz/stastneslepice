<?php

namespace App\Models;

class FeedPurchase extends Model
{
    protected static string $table = 'feed_purchases';

    public static function getAll(): array
    {
        return static::query(
            "SELECT fp.*, ft.name AS feed_type_name
             FROM feed_purchases fp
             JOIN feed_types ft ON fp.feed_type_id = ft.id
             ORDER BY fp.purchased_at DESC, fp.id DESC"
        );
    }

    public static function getTotalSpent(int $months = 12): float
    {
        return (float) static::queryValue(
            "SELECT COALESCE(SUM(total_price), 0)
             FROM feed_purchases
             WHERE purchased_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)",
            [$months]
        );
    }

    public static function getMonthlySpending(int $months = 12): array
    {
        return static::query(
            "SELECT DATE_FORMAT(purchased_at, '%Y-%m') AS month,
                    SUM(total_price) AS total_spent,
                    SUM(quantity_kg) AS total_kg
             FROM feed_purchases
             WHERE purchased_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY DATE_FORMAT(purchased_at, '%Y-%m')
             ORDER BY month ASC",
            [$months]
        );
    }
}
