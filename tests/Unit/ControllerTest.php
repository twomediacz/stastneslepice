<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\TestableController;
use Tests\Support\TestResponse;

final class ControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $_POST = [];
        $_SERVER['CONTENT_TYPE'] = '';
    }

    public function testGetPostDataReturnsRegularPostPayload(): void
    {
        $controller = new TestableController();
        $_POST = [
            'name' => 'Pepa',
            'amount' => '12',
        ];

        self::assertSame($_POST, $controller->readPostData());
    }

    public function testJsonOutputsUtf8JsonAndStatusCode(): void
    {
        $controller = new TestableController();
        $response = TestResponse::capture(
            static fn() => $controller->sendJson(['message' => 'Příliš žluťoučký kůň'], 202)
        );

        self::assertTrue($response['aborted']);
        self::assertSame(202, $response['status']);
        self::assertSame('{"message":"Příliš žluťoučký kůň"}', $response['output']);
    }

    public function testJsonErrorOutputsErrorPayloadAndStatusCode(): void
    {
        $controller = new TestableController();
        $response = TestResponse::capture(
            static fn() => $controller->sendJsonError('Něco se pokazilo', 422)
        );

        self::assertTrue($response['aborted']);
        self::assertSame(422, $response['status']);
        self::assertSame('{"error":"Něco se pokazilo"}', $response['output']);
    }
}
