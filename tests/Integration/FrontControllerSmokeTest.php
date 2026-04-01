<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\DatabaseTestCase;

final class FrontControllerSmokeTest extends DatabaseTestCase
{
    public function testLoginRouteRendersThroughPublicIndex(): void
    {
        $result = $this->runFrontController('/login');

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('<h2>Přihlášení</h2>', $result['stdout']);
    }

    public function testAdvancedAlmanachRouteRendersThroughPublicIndex(): void
    {
        $result = $this->runFrontController('/almanach/pokrocily');

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('podrobných rad', $result['stdout']);
        self::assertStringContainsString('Kurník – údržba, prostředí a provoz', $result['stdout']);
    }

    public function testSettingsApiRouteReturnsPublicSettingsThroughFrontController(): void
    {
        $result = $this->runFrontController('/api/settings');

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = json_decode($result['stdout'], true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('settings', $payload);
        self::assertArrayHasKey('locale_name', $payload['settings']);
        self::assertArrayNotHasKey('weather_api_key', $payload['settings']);
    }

    public function testPostEggEndpointReturnsValidationErrorThroughFrontController(): void
    {
        $result = $this->runFrontController(
            '/api/eggs',
            'POST',
            ['date' => date('Y-m-d'), 'egg_count' => -2],
            ['user_id' => 1, 'username' => 'admin', 'role' => 'admin']
        );
        $payload = json_decode($result['stdout'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertSame('Počet vajec nemůže být záporný.', $payload['error']);
    }

    private function runFrontController(string $uri, string $method = 'GET', array $post = [], array $session = []): array
    {
        $command = [
            PHP_BINARY,
            '-r',
            sprintf(
                'require "vendor/autoload.php"; Tests\\Support\\TestDatabase::createFresh(%s); putenv("DB_DRIVER=sqlite"); putenv("DB_DATABASE=%s"); $_SERVER["REQUEST_METHOD"]=%s; $_SERVER["REQUEST_URI"]=%s; $_POST=%s; session_start(); $_SESSION=%s; chdir(%s); require "public/index.php";',
                var_export($this->dbPath, true),
                addslashes($this->dbPath),
                var_export($method, true),
                var_export($uri, true),
                var_export($post, true),
                var_export($session, true),
                var_export(dirname(__DIR__, 2), true)
            ),
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__, 2));
        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exitCode' => $exitCode,
        ];
    }
}
