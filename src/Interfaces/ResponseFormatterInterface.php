<?php

namespace Whilesmart\Campaigns\Interfaces;

use Illuminate\Http\JsonResponse;

interface ResponseFormatterInterface
{
    public function success(mixed $data = null, string $message = 'OK', int $statusCode = 200): JsonResponse;

    public function failure(string $message = 'Operation failed', int $statusCode = 400, array $errors = []): JsonResponse;
}
