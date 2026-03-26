<?php

namespace App\Core;

class App
{
    private array $config;
    private Router $router;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/config.php';
        date_default_timezone_set($this->config['app']['timezone']);

        Database::connect($this->config['db']);
        $this->router = new Router();
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $this->router->dispatch($method, $uri);
    }
}
