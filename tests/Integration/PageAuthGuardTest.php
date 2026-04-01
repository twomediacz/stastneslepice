<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\FeedingController;
use App\Controllers\FinanceController;
use App\Controllers\UsersController;
use Tests\Support\DatabaseTestCase;
use Tests\Support\TestResponse;

final class PageAuthGuardTest extends DatabaseTestCase
{
    public function testProtectedPagesRedirectGuestsToLogin(): void
    {
        foreach ([new FeedingController(), new FinanceController(), new UsersController()] as $controller) {
            $response = TestResponse::capture(static fn() => $controller->index());

            self::assertTrue($response['aborted']);
            self::assertSame(302, $response['status']);
            self::assertSame('', $response['output']);
        }
    }
}
