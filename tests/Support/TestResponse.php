<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\TestAbortException;
use Throwable;

final class TestResponse
{
    public static function capture(callable $callback): array
    {
        http_response_code(200);
        ob_start();

        try {
            $callback();
            $aborted = false;
        } catch (TestAbortException) {
            $aborted = true;
        } catch (Throwable $throwable) {
            $output = ob_get_clean();
            http_response_code(200);
            throw $throwable;
        }

        $output = ob_get_clean();
        $status = http_response_code();
        http_response_code(200);

        return [
            'aborted' => $aborted,
            'output' => $output,
            'status' => $status,
        ];
    }
}
