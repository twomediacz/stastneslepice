<?php

namespace App\Controllers;

abstract class Controller
{
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function jsonError(string $message, int $status = 400): void
    {
        $this->json(['error' => $message], $status);
    }

    protected function getPostData(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            return json_decode($raw, true) ?: [];
        }
        return $_POST;
    }
}
