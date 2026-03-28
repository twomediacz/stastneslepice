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
        return static::query(
            "SELECT * FROM expenses
             WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             ORDER BY expense_date DESC, id DESC",
            [$days]
        );
    }

    public static function getTotalByCategory(int $months = 12): array
    {
        return static::query(
            "SELECT category, SUM(amount) AS total
             FROM expenses
             WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY category
             ORDER BY total DESC",
            [$months]
        );
    }

    public static function getMonthlyTotal(int $months = 12): array
    {
        return static::query(
            "SELECT DATE_FORMAT(expense_date, '%Y-%m') AS month,
                    SUM(amount) AS total
             FROM expenses
             WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
             ORDER BY month ASC",
            [$months]
        );
    }

    public static function getTotalAmount(int $months = 12): float
    {
        return (float) static::queryValue(
            "SELECT COALESCE(SUM(amount), 0)
             FROM expenses
             WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)",
            [$months]
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
