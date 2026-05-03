<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = $this->normalizeUri($uri);

        if (isset($this->routes[$method][$uri])) {
            [$controllerClass, $action] = $this->routes[$method][$uri];
            $controller = new $controllerClass();
            $controller->$action();
            return;
        }

        http_response_code(404);
        echo '404 – Stránka nenalezena';
    }

    private function normalizeUri(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($scriptName !== '') {
            $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
            if ($scriptDir !== '' && $scriptDir !== '.' && $scriptDir !== '/' && str_starts_with($path, $scriptDir . '/')) {
                $path = substr($path, strlen($scriptDir));
            }
        }

        if (str_starts_with($path, '/index.php/')) {
            $path = substr($path, 10);
        } elseif ($path === '/index.php') {
            $path = '/';
        }

        if ($path === '/public') {
            $path = '/';
        } elseif (str_starts_with($path, '/public/')) {
            $path = substr($path, 7);
        }

        return rtrim($path, '/') ?: '/';
    }
}
