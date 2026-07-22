<?php

namespace Tests\Support;

use Illuminate\Http\JsonResponse;
use Whilesmart\Campaigns\Interfaces\ResponseFormatterInterface;

class EnvelopeFormatter implements ResponseFormatterInterface
{
    public function success(mixed $data = null, string $message = 'OK', int $statusCode = 200): JsonResponse
    {
        return response()->json(['ok' => true, 'note' => $message, 'payload' => $data], $statusCode);
    }

    public function failure(string $message = 'Operation failed', int $statusCode = 400, array $errors = []): JsonResponse
    {
        return response()->json(['ok' => false, 'note' => $message, 'errors' => $errors], $statusCode);
    }
}
