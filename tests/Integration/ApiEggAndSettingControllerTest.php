<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Api\EggController;
use App\Controllers\Api\SettingController;
use App\Models\Setting;
use Tests\Support\DatabaseTestCase;
use Tests\Support\TestResponse;

final class ApiEggAndSettingControllerTest extends DatabaseTestCase
{
    public function testSettingIndexHidesSensitiveKeysForGuest(): void
    {
        $controller = new SettingController();

        $response = TestResponse::capture(static fn() => $controller->index());
        $payload = json_decode($response['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response['status']);
        self::assertArrayHasKey('locale_name', $payload['settings']);
        self::assertArrayNotHasKey('weather_api_key', $payload['settings']);
        self::assertArrayNotHasKey('climate_api_token', $payload['settings']);
    }

    public function testSettingUpdatePersistsValueForAuthenticatedUser(): void
    {
        $_SESSION = ['user_id' => 1, 'username' => 'admin', 'role' => 'admin'];
        $_POST = [
            'setting_key' => 'locale_name',
            'setting_value' => 'Nové Doloplazy',
        ];

        $controller = new SettingController();
        $response = TestResponse::capture(static fn() => $controller->update());
        $payload = json_decode($response['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response['status']);
        self::assertTrue($payload['success']);
        self::assertSame('Nové Doloplazy', Setting::get('locale_name'));
    }

    public function testEggStoreValidatesNegativeCount(): void
    {
        $_SESSION = ['user_id' => 1];
        $_POST = [
            'date' => date('Y-m-d'),
            'egg_count' => -1,
        ];

        $controller = new EggController();
        $response = TestResponse::capture(static fn() => $controller->store());
        $payload = json_decode($response['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response['status']);
        self::assertSame('Počet vajec nemůže být záporný.', $payload['error']);
    }

    public function testEggStoreAndMonthlyIndexReturnAggregatedData(): void
    {
        $_SESSION = ['user_id' => 1];
        $controller = new EggController();

        $_POST = [
            'date' => date('Y-m-d'),
            'egg_count' => 4,
            'note' => 'Dnes',
        ];
        $storeResponse = TestResponse::capture(static fn() => $controller->store());
        $storePayload = json_decode($storeResponse['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($storePayload['success']);
        self::assertSame(4, $storePayload['total']);

        $_GET = ['group' => 'month', 'months' => 2];
        $indexResponse = TestResponse::capture(static fn() => $controller->index());
        $indexPayload = json_decode($indexResponse['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('month', $indexPayload['grouped']);
        self::assertSame(4, $indexPayload['total']);
        self::assertEquals(4.0, $indexPayload['average']);
        self::assertCount(1, $indexPayload['records']);
    }
}
