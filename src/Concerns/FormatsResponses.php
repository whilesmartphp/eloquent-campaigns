<?php

namespace Whilesmart\Campaigns\Concerns;

use Illuminate\Http\JsonResponse;
use Whilesmart\Campaigns\Interfaces\ResponseFormatterInterface;
use Whilesmart\Campaigns\ResponseFormatters\DefaultResponseFormatter;

/**
 * Routes every controller response through the configured formatter, so a host
 * can reshape the envelope (e.g. add a message field, change the pagination
 * shape) without forking the package.
 */
trait FormatsResponses
{
    protected function responseFormatter(): ResponseFormatterInterface
    {
        return app(config('campaigns.response_formatter', DefaultResponseFormatter::class));
    }

    protected function success(mixed $data = null, string $message = 'OK', int $statusCode = 200): JsonResponse
    {
        return $this->responseFormatter()->success($data, $message, $statusCode);
    }

    protected function failure(string $message = 'Operation failed', int $statusCode = 400, array $errors = []): JsonResponse
    {
        return $this->responseFormatter()->failure($message, $statusCode, $errors);
    }
}
