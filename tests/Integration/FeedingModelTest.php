<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\FeedPurchase;
use App\Models\FeedType;
use App\Models\FeedingRecord;
use Tests\Support\DatabaseTestCase;

final class FeedingModelTest extends DatabaseTestCase
{
    public function testFeedingTotalsBreakdownsAndPurchasesAreCalculated(): void
    {
        $grainId = FeedType::insert([
            'name' => 'Psenice',
            'price_per_kg' => 12.5,
            'palatability' => 4,
        ]);
        $mixId = FeedType::insert([
            'name' => 'Směs',
            'price_per_kg' => 18.0,
            'palatability' => 5,
        ]);

        FeedingRecord::insert([
            'feed_type_id' => $grainId,
            'record_date' => date('Y-m-d'),
            'amount_kg' => 1.2,
        ]);
        FeedingRecord::insert([
            'feed_type_id' => $mixId,
            'record_date' => date('Y-m-d', strtotime('-1 day')),
            'amount_kg' => 0.8,
        ]);
        FeedingRecord::insert([
            'feed_type_id' => $grainId,
            'record_date' => date('Y-m-d', strtotime('-45 days')),
            'amount_kg' => 2.0,
        ]);

        FeedPurchase::insert([
            'feed_type_id' => $grainId,
            'purchased_at' => date('Y-m-d'),
            'quantity_kg' => 25,
            'total_price' => 300,
        ]);
        FeedPurchase::insert([
            'feed_type_id' => $mixId,
            'purchased_at' => date('Y-m-d', strtotime('-20 days')),
            'quantity_kg' => 10,
            'total_price' => 180,
        ]);

        $recent = FeedingRecord::getRecent(30);
        $byType = FeedingRecord::getConsumptionByType(30);
        $monthly = FeedingRecord::getMonthlyConsumption(3);
        $purchaseMonthly = FeedPurchase::getMonthlySpending(3);

        self::assertCount(2, $recent);
        self::assertSame(2.0, FeedingRecord::getTotalKg(30));
        self::assertSame(29.4, FeedingRecord::getTotalCost(30));
        self::assertSame(1.0, FeedingRecord::getDailyAverage(30));
        self::assertSame('Psenice', $byType[0]['name']);
        self::assertNotEmpty($monthly);
        self::assertSame(480.0, FeedPurchase::getTotalSpent(3));
        self::assertCount(2, $purchaseMonthly);
    }
}
