<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Api\ChickenController;
use App\Models\Chicken;
use Tests\Support\DatabaseTestCase;
use Tests\Support\TestResponse;

final class ApiChickenControllerTest extends DatabaseTestCase
{
    public function testStorePersistsRingColorAsNormalizedHex(): void
    {
        $_SESSION = ['user_id' => 1];
        $_POST = [
            'name' => 'Běla',
            'ring_color' => '#F2C94C',
            'birth_date' => '',
            'acquired_date' => '',
            'end_date' => '',
        ];

        $controller = new ChickenController();
        $response = TestResponse::capture(static fn() => $controller->store());
        $payload = json_decode($response['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response['status']);
        self::assertTrue($payload['success']);
        self::assertSame('#f2c94c', $payload['chicken']['ring_color']);
        self::assertSame('#f2c94c', Chicken::findById((int) $payload['chicken']['id'])['ring_color']);
    }

    public function testStoreRejectsInvalidRingColor(): void
    {
        $_SESSION = ['user_id' => 1];
        $_POST = [
            'name' => 'Kropenka',
            'ring_color' => 'modrá',
            'birth_date' => '',
            'acquired_date' => '',
            'end_date' => '',
        ];

        $controller = new ChickenController();
        $response = TestResponse::capture(static fn() => $controller->store());
        $payload = json_decode($response['output'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response['status']);
        self::assertSame('Barva kroužku musí být ve formátu #RRGGBB.', $payload['error']);
    }
}
