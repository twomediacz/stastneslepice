<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Auth;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Tests\Support\TestResponse;

final class AuthTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testCheckAndUserReturnExpectedValuesAfterLogin(): void
    {
        session_id('auth-test-login');
        session_start();

        Auth::login(7, 'pepina', 'admin');

        self::assertTrue(Auth::check());
        self::assertSame(
            [
                'id' => 7,
                'username' => 'pepina',
                'role' => 'admin',
            ],
            Auth::user()
        );

        session_write_close();
    }

    #[RunInSeparateProcess]
    public function testUserReturnsNullWhenNobodyIsLoggedIn(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $_SESSION = [];

        self::assertFalse(Auth::check());
        self::assertNull(Auth::user());
    }

    #[RunInSeparateProcess]
    public function testLogoutClearsAuthenticatedUser(): void
    {
        session_id('auth-test-logout');
        session_start();

        Auth::login(3, 'franta', 'editor');
        Auth::logout();

        self::assertFalse(Auth::check());
        self::assertNull(Auth::user());
    }

    public function testRequireAuthApiReturnsUnauthorizedJsonForGuest(): void
    {
        $_SESSION = [];
        $response = TestResponse::capture(static fn() => Auth::requireAuthApi());

        self::assertTrue($response['aborted']);
        self::assertSame(401, $response['status']);
        self::assertSame('{"error":"Nep\u0159ihl\u00e1\u0161en"}', $response['output']);
    }

    public function testRequireAuthRedirectsGuest(): void
    {
        $_SESSION = [];

        $response = TestResponse::capture(static fn() => Auth::requireAuth());

        self::assertTrue($response['aborted']);
        self::assertSame(302, $response['status']);
        self::assertSame('', $response['output']);
    }
}
