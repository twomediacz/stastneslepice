<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\FeedType;
use App\Models\FeedingRecord;
use App\Models\FeedPurchase;

class FeedingController
{
    public function index(): void
    {
        Auth::requireAuth();

        $feedTypes = FeedType::getAll();
        $recentRecords = FeedingRecord::getRecent(30);
        $purchases = FeedPurchase::getAll();

        $totalKgMonth = FeedingRecord::getTotalKg(30);
        $totalCostMonth = FeedingRecord::getTotalCost(30);
        $dailyAvg = FeedingRecord::getDailyAverage(30);

        View::render('feeding', [
            'title' => 'Krmení',
            'feedTypes' => $feedTypes,
            'recentRecords' => $recentRecords,
            'purchases' => $purchases,
            'totalKgMonth' => $totalKgMonth,
            'totalCostMonth' => $totalCostMonth,
            'dailyAvg' => $dailyAvg,
        ]);
    }
}
