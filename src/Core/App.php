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

        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = 365 * 24 * 60 * 60; // 1 rok
            ini_set('session.gc_maxlifetime', $lifetime);
            session_set_cookie_params($lifetime);
            session_start();
        }

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
