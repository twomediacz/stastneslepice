<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    protected function tearDown(): void
    {
        http_response_code(200);
        RouterTestController::$called = false;
    }

    public function testDispatchNormalizesTrailingSlashAndCallsRegisteredHandler(): void
    {
        $router = new Router();
        $router->get('/test', [RouterTestController::class, 'show']);

        ob_start();
        $router->dispatch('GET', '/test/');
        $output = ob_get_clean();

        self::assertTrue(RouterTestController::$called);
        self::assertSame('ok', $output);
        self::assertNotSame(404, http_response_code());
    }

    public function testDispatchReturns404ForUnknownRoute(): void
    {
        $router = new Router();

        ob_start();
        $router->dispatch('GET', '/missing');
        $output = ob_get_clean();

        self::assertSame(404, http_response_code());
        self::assertSame('404 – Stránka nenalezena', $output);
    }

    public function testDispatchIgnoresQueryStringDuringRouteMatching(): void
    {
        $router = new Router();
        $router->get('/api/test', [RouterTestController::class, 'show']);

        ob_start();
        $router->dispatch('GET', '/api/test?days=30&group=month');
        $output = ob_get_clean();

        self::assertTrue(RouterTestController::$called);
        self::assertSame('ok', $output);
    }
}

final class RouterTestController
{
    public static bool $called = false;

    public function show(): void
    {
        self::$called = true;
        echo 'ok';
    }
}
