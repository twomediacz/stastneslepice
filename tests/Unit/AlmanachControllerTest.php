<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\AlmanachController;
use PHPUnit\Framework\TestCase;

final class AlmanachControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testIndexRendersBasicAlmanachContent(): void
    {
        $_SERVER['REQUEST_URI'] = '/almanach';
        $controller = new AlmanachController();

        ob_start();
        $controller->index();
        $output = ob_get_clean();

        self::assertStringContainsString('praktických rad', $output);
        self::assertStringContainsString('Než začnete', $output);
        self::assertStringContainsString('Výběr plemene', $output);
    }

    public function testPokrocilyRendersExtendedAlmanachContent(): void
    {
        $_SERVER['REQUEST_URI'] = '/almanach/pokrocily';
        $controller = new AlmanachController();

        ob_start();
        $controller->pokrocily();
        $output = ob_get_clean();

        self::assertStringContainsString('podrobných rad', $output);
        self::assertStringContainsString('Kurník – údržba, prostředí a provoz', $output);
        self::assertStringContainsString('Při jakémkoliv zdravotním problému u slepic', $output);
    }
}
