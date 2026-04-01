<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Api\FeedingController;
use App\Controllers\Api\FinanceController;
use App\Models\FeedPurchase;
use App\Models\FeedType;
use App\Models\FeedingRecord;
use App\Models\EggRecord;
use App\Models\EggTransaction;
use App\Models\Expense;
use Tests\Support\DatabaseTestCase;
use Tests\Support\TestResponse;

final class ApiFeedingAndFinanceControllerTest extends DatabaseTestCase
{
    public function testStoreTypeValidatesInput(): void
    {
        $_SESSION = ['user_id' => 1];
        $_POST = [
            'name' => '',
            'price_per_kg' => 10,
        ];

        $controller = new FeedingController();
        $response = TestResponse::capture(static fn() => $controller->storeType());
        $payload = json_decode($response['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response['status']);
        self::assertSame('Název krmiva je povinný.', $payload['error']);
    }

    public function testStoreRecordPersistsAndReturnsRollingStats(): void
    {
        $_SESSION = ['user_id' => 1];
        $feedTypeId = FeedType::insert([
            'name' => 'Granule',
            'price_per_kg' => 20,
        ]);

        $_POST = [
            'feed_type_id' => $feedTypeId,
            'record_date' => date('Y-m-d'),
            'amount_kg' => 1.5,
            'note' => 'Večer',
        ];

        $controller = new FeedingController();
        $response = TestResponse::capture(static fn() => $controller->storeRecord());
        $payload = json_decode($response['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($payload['success']);
        self::assertSame('Granule', $payload['record']['feed_type_name']);
        self::assertSame(1.5, $payload['totalKg']);
        self::assertEquals(30.0, $payload['totalCost']);
        self::assertEquals(1.5, $payload['dailyAvg']);
    }

    public function testFinanceSummaryReturnsCombinedAggregates(): void
    {
        $_SESSION = ['user_id' => 1];
        $feedTypeId = FeedType::insert([
            'name' => 'Pšenice',
            'price_per_kg' => 11,
        ]);

        FeedPurchase::insert([
            'feed_type_id' => $feedTypeId,
            'purchased_at' => date('Y-m-d'),
            'quantity_kg' => 20,
            'total_price' => 220,
        ]);
        FeedingRecord::insert([
            'feed_type_id' => $feedTypeId,
            'record_date' => date('Y-m-d'),
            'amount_kg' => 1.0,
        ]);
        Expense::insert([
            'expense_date' => date('Y-m-d'),
            'category' => 'equipment',
            'amount' => 99,
        ]);
        EggRecord::upsert(date('Y-m-d'), 8, null);
        EggTransaction::insert([
            'transaction_date' => date('Y-m-d'),
            'type' => 'sale',
            'quantity' => 12,
            'price_total' => 84,
            'recipient' => 'Soused',
        ]);

        $_GET = ['months' => 12];

        $controller = new FinanceController();
        $response = TestResponse::capture(static fn() => $controller->summary());
        $payload = json_decode($response['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response['status']);
        self::assertEquals(220.0, $payload['feedTotal']);
        self::assertCount(1, $payload['feedMonthly']);
        self::assertCount(1, $payload['expenseMonthly']);
        self::assertCount(1, $payload['revenueMonthly']);
        self::assertSame('equipment', $payload['expensesByCategory'][0]['category']);
    }
}
