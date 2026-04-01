<?php

namespace App\Models;

class Expense extends Model
{
    protected static string $table = 'expenses';

    public static function getAll(): array
    {
        return static::query(
            "SELECT * FROM expenses ORDER BY expense_date DESC, id DESC"
        );
    }

    public static function getRecent(int $days = 90): array
    {
        [$condition, $params] = static::dateRangeCondition('expense_date', $days, 'day');

        return static::query(
            "SELECT * FROM expenses
             WHERE {$condition}
             ORDER BY expense_date DESC, id DESC",
            $params
        );
    }

    public static function getTotalByCategory(int $months = 12): array
    {
        [$condition, $params] = static::dateRangeCondition('expense_date', $months, 'month');

        return static::query(
            "SELECT category, SUM(amount) AS total
             FROM expenses
             WHERE {$condition}
             GROUP BY category
             ORDER BY total DESC",
            $params
        );
    }

    public static function getMonthlyTotal(int $months = 12): array
    {
        [$condition, $params] = static::dateRangeCondition('expense_date', $months, 'month');
        $monthBucket = static::monthBucket('expense_date');

        return static::query(
            "SELECT {$monthBucket} AS month,
                    SUM(amount) AS total
             FROM expenses
             WHERE {$condition}
             GROUP BY {$monthBucket}
             ORDER BY month ASC",
            $params
        );
    }

    public static function getTotalAmount(int $months = 12): float
    {
        [$condition, $params] = static::dateRangeCondition('expense_date', $months, 'month');

        return (float) static::queryValue(
            "SELECT COALESCE(SUM(amount), 0)
             FROM expenses
             WHERE {$condition}",
            $params
        );
    }

    public static function categoryLabel(string $cat): string
    {
        return match ($cat) {
            'bedding' => 'Podestýlka',
            'vet' => 'Veterina',
            'equipment' => 'Vybavení',
            'other' => 'Ostatní',
            default => $cat,
        };
    }
}
