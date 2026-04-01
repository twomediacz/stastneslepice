<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected string $dbPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = TestDatabase::createFresh();

        $_GET = [];
        $_POST = [];
        $_SESSION = [];
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['CONTENT_TYPE'] = '';
    }

    protected function tearDown(): void
    {
        TestDatabase::destroy($this->dbPath);
        http_response_code(200);

        parent::tearDown();
    }
}
