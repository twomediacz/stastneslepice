<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\View;
use PHPUnit\Framework\TestCase;

final class ViewTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER['REQUEST_URI'] = '/login';
    }

    public function testRenderCombinesPageAndLayoutWithProvidedData(): void
    {
        ob_start();
        View::render('login', [
            'title' => 'Test login',
            'error' => 'Špatné heslo',
            'username' => 'maruna',
        ]);
        $output = ob_get_clean();

        self::assertStringContainsString('<title>Test login</title>', $output);
        self::assertStringContainsString('Špatné heslo', $output);
        self::assertStringContainsString('value="maruna"', $output);
        self::assertStringContainsString('<footer class="site-footer">', $output);
    }
}
