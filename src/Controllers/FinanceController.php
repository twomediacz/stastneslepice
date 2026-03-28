<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Expense;
use App\Models\EggTransaction;
use App\Models\FeedPurchase;
use App\Models\EggRecord;
use App\Models\Setting;

class FinanceController
{
    public function index(): void
    {
        Auth::requireAuth();

        $expenses = Expense::getRecent(365);
        $eggTransactions = EggTransaction::getRecent(365);

        // Náklady za 12 měsíců
        $feedCosts = FeedPurchase::getTotalSpent(12);
        $otherCosts = Expense::getTotalAmount(12);
        $totalCosts = $feedCosts + $otherCosts;

        // Příjmy za 12 měsíců
        $eggRevenue = EggTransaction::getTotalRevenue(12);
        $eggsSold = EggTransaction::getTotalSold(12);
        $eggsGifted = EggTransaction::getTotalGifted(12);

        // Hodnota vajec
        $totalEggs = EggRecord::getTotalEggs();
        $eggMarketPrice = (float) (Setting::get('egg_market_price') ?? 5.50);
        $costPerEgg = $totalEggs > 0 ? $totalCosts / $totalEggs : 0;
        $eggMarketValue = $totalEggs * $eggMarketPrice;
        $eggSavings = $eggMarketValue - $totalCosts;

        // Bilance
        $balance = $eggRevenue - $totalCosts;

        // Náklady dle kategorií
        $expensesByCategory = Expense::getTotalByCategory(12);

        View::render('finance', [
            'title' => 'Finance',
            'expenses' => $expenses,
            'eggTransactions' => $eggTransactions,
            'feedCosts' => $feedCosts,
            'otherCosts' => $otherCosts,
            'totalCosts' => $totalCosts,
            'eggRevenue' => $eggRevenue,
            'eggsSold' => $eggsSold,
            'eggsGifted' => $eggsGifted,
            'totalEggs' => $totalEggs,
            'eggMarketPrice' => $eggMarketPrice,
            'costPerEgg' => $costPerEgg,
            'eggMarketValue' => $eggMarketValue,
            'eggSavings' => $eggSavings,
            'balance' => $balance,
            'expensesByCategory' => $expensesByCategory,
        ]);
    }
}
