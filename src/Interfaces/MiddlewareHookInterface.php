<?php

namespace Whilesmart\Campaigns\Interfaces;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface MiddlewareHookInterface
{
    /**
     * Handle the request before the action runs. Return a Request to replace it.
     */
    public function before(Request $request, string $action): ?Request;

    /**
     * Handle the response after the action runs. Return the (possibly modified) response.
     */
    public function after(Request $request, JsonResponse $response, string $action): JsonResponse;
}
