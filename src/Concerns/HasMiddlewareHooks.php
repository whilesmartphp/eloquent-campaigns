<?php

namespace Whilesmart\Campaigns\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Whilesmart\Campaigns\Interfaces\MiddlewareHookInterface;

/**
 * Lets a host observe and reshape each action's request and response by
 * registering hook classes in config('campaigns.middleware_hooks'). Actions are
 * identified by a string such as 'campaigns.store' or 'campaigns.analytics'.
 */
trait HasMiddlewareHooks
{
    protected function runBeforeHooks(Request $request, string $action): Request
    {
        foreach ($this->resolveHooks() as $hook) {
            $result = $hook->before($request, $action);
            if ($result instanceof Request) {
                $request = $result;
            }
        }

        return $request;
    }

    protected function runAfterHooks(Request $request, JsonResponse $response, string $action): JsonResponse
    {
        foreach ($this->resolveHooks() as $hook) {
            $result = $hook->after($request, $response, $action);
            if ($result instanceof JsonResponse) {
                $response = $result;
            }
        }

        return $response;
    }

    /**
     * @return array<int, MiddlewareHookInterface>
     */
    private function resolveHooks(): array
    {
        $hooks = [];

        foreach ((array) config('campaigns.middleware_hooks', []) as $hookClass) {
            if (is_string($hookClass) && class_exists($hookClass)) {
                $hook = app($hookClass);
                if ($hook instanceof MiddlewareHookInterface) {
                    $hooks[] = $hook;
                }
            }
        }

        return $hooks;
    }
}
