<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\EggTransaction;
use App\Models\Expense;
use Tests\Support\DatabaseTestCase;

final class FinanceModelTest extends DatabaseTestCase
{
    public function testExpenseAndEggTransactionAggregationsReturnExpectedTotals(): void
    {
        Expense::insert([
            'expense_date' => date('Y-m-d'),
            'category' => 'vet',
            'amount' => 250,
        ]);
        Expense::insert([
            'expense_date' => date('Y-m-d', strtotime('-10 days')),
            'category' => 'bedding',
            'amount' => 100,
        ]);
        Expense::insert([
            'expense_date' => date('Y-m-d', strtotime('-400 days')),
            'category' => 'other',
            'amount' => 999,
        ]);

        EggTransaction::insert([
            'transaction_date' => date('Y-m-d'),
            'type' => 'sale',
            'quantity' => 30,
            'price_total' => 180,
            'recipient' => 'Sousedi',
        ]);
        EggTransaction::insert([
            'transaction_date' => date('Y-m-d', strtotime('-2 days')),
            'type' => 'gift',
            'quantity' => 12,
            'price_total' => 0,
        ]);

        self::assertCount(2, Expense::getRecent(30));
        self::assertSame(350.0, Expense::getTotalAmount(12));
        self::assertCount(2, Expense::getTotalByCategory(12));
        self::assertNotEmpty(Expense::getMonthlyTotal(12));
        self::assertSame(180.0, EggTransaction::getTotalRevenue(12));
        self::assertSame(30, EggTransaction::getTotalSold(12));
        self::assertSame(12, EggTransaction::getTotalGifted(12));
        self::assertSame('Darování', EggTransaction::typeLabel('gift'));
        self::assertNotEmpty(EggTransaction::getMonthlyRevenue(12));
    }
}
