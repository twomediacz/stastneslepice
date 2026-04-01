<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Controllers\Controller;

final class TestableController extends Controller
{
    public function readPostData(): array
    {
        return $this->getPostData();
    }

    public function sendJson(mixed $data, int $status = 200): void
    {
        $this->json($data, $status);
    }

    public function sendJsonError(string $message, int $status = 400): void
    {
        $this->jsonError($message, $status);
    }
}
