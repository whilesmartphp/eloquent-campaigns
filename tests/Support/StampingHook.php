<?php

namespace Tests\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Whilesmart\Campaigns\Interfaces\MiddlewareHookInterface;

/**
 * before: forces the list to active-only. after: stamps the action + a marker
 * onto the JSON, so a test can prove both phases ran.
 */
class StampingHook implements MiddlewareHookInterface
{
    public function before(Request $request, string $action): ?Request
    {
        $request->merge(['is_active' => true]);

        return $request;
    }

    public function after(Request $request, JsonResponse $response, string $action): JsonResponse
    {
        $payload = $response->getData(true);
        $payload['hooked'] = true;
        $payload['action'] = $action;
        $response->setData($payload);

        return $response;
    }
}
