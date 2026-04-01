<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\AuthController;
use PHPUnit\Framework\TestCase;
use Tests\Support\TestResponse;

final class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_URI'] = '/login';
    }

    public function testShowLoginRendersLoginFormForGuest(): void
    {
        $controller = new AuthController();

        ob_start();
        $controller->showLogin();
        $output = ob_get_clean();

        self::assertStringContainsString('<h2>Přihlášení</h2>', $output);
        self::assertStringContainsString('<form method="post" action="/login">', $output);
        self::assertStringNotContainsString('alert alert--error', $output);
    }

    public function testLoginWithMissingCredentialsRendersValidationError(): void
    {
        $controller = new AuthController();
        $_POST = [
            'username' => '  pepa  ',
            'password' => '',
        ];

        ob_start();
        $controller->login();
        $output = ob_get_clean();

        self::assertStringContainsString('Vyplňte uživatelské jméno a heslo.', $output);
        self::assertStringContainsString('value="pepa"', $output);
        self::assertStringContainsString('<h2>Přihlášení</h2>', $output);
    }

    public function testShowLoginRedirectsAuthenticatedUserToHomepage(): void
    {
        $_SESSION = ['user_id' => 1, 'username' => 'admin', 'role' => 'admin'];
        $controller = new AuthController();

        $response = TestResponse::capture(static fn() => $controller->showLogin());

        self::assertTrue($response['aborted']);
        self::assertSame(302, $response['status']);
        self::assertSame('', $response['output']);
    }
}
