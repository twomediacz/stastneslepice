<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Api\MaintenanceController;
use App\Controllers\Api\NoteController;
use App\Controllers\Api\TextSnippetController;
use App\Models\BeddingChange;
use App\Models\Setting;
use Tests\Support\DatabaseTestCase;
use Tests\Support\TestResponse;

final class ApiNoteSnippetAndMaintenanceControllerTest extends DatabaseTestCase
{
    public function testNoteStoreRejectsEmptyContent(): void
    {
        $_SESSION = ['user_id' => 1];
        $_POST = ['content' => '   '];

        $controller = new NoteController();
        $response = TestResponse::capture(static fn() => $controller->store());
        $payload = json_decode($response['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response['status']);
        self::assertSame('Poznámka nemůže být prázdná.', $payload['error']);
    }

    public function testNoteStoreAndIndexReturnCreatedNote(): void
    {
        $_SESSION = ['user_id' => 1];
        $controller = new NoteController();

        $_POST = [
            'note_date' => date('Y-m-d'),
            'content' => 'Nakoupit zrní',
        ];
        $storeResponse = TestResponse::capture(static fn() => $controller->store());
        $storePayload = json_decode($storeResponse['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($storePayload['success']);
        self::assertSame('Nakoupit zrní', $storePayload['note']['content']);

        $_GET = ['limit' => 10];
        $indexResponse = TestResponse::capture(static fn() => $controller->index());
        $indexPayload = json_decode($indexResponse['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $indexPayload['notes']);
        self::assertSame('Nakoupit zrní', $indexPayload['notes'][0]['content']);
    }

    public function testSnippetStoreAndRandomEndpointWork(): void
    {
        $_SESSION = ['user_id' => 1];
        $controller = new TextSnippetController();

        $_POST = [
            'type' => 'tip',
            'content' => 'Kontroluj napáječku.',
        ];
        $storeResponse = TestResponse::capture(static fn() => $controller->store());
        $storePayload = json_decode($storeResponse['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($storePayload['success']);

        $_GET = ['type' => 'tip'];
        $randomResponse = TestResponse::capture(static fn() => $controller->random());
        $randomPayload = json_decode($randomResponse['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('tip', $randomPayload['snippet']['type']);
        self::assertSame('Kontroluj napáječku.', $randomPayload['snippet']['content']);
    }

    public function testBeddingIntervalAndQuickLogComputeNextChange(): void
    {
        $_SESSION = ['user_id' => 1];
        $controller = new MaintenanceController();

        BeddingChange::insert([
            'changed_at' => '2026-03-20 08:00:00',
            'note' => 'Předchozí výměna',
        ]);

        $_POST = ['interval_days' => 10];
        $intervalResponse = TestResponse::capture(static fn() => $controller->beddingInterval());
        $intervalPayload = json_decode($intervalResponse['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($intervalPayload['success']);
        self::assertSame(10, $intervalPayload['interval_days']);
        self::assertSame('2026-03-30', $intervalPayload['next_change']);
        self::assertSame('10', Setting::get('bedding_interval_days'));

        $_POST = ['note' => 'Rychlý zápis'];
        $quickResponse = TestResponse::capture(static fn() => $controller->beddingQuickLog());
        $quickPayload = json_decode($quickResponse['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($quickPayload['success']);
        self::assertSame(10, $quickPayload['interval_days']);
        self::assertSame('Rychlý zápis', $quickPayload['record']['note']);
    }

    public function testRepairStoreValidatesRequiredDate(): void
    {
        $_SESSION = ['user_id' => 1];
        $_POST = ['repaired_at' => '', 'note' => 'Plot'];

        $controller = new MaintenanceController();
        $response = TestResponse::capture(static fn() => $controller->repairStore());
        $payload = json_decode($response['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response['status']);
        self::assertSame('Datum je povinné.', $payload['error']);
    }
}
