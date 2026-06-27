<?php

return [
    'register_routes' => env('CAMPAIGNS_REGISTER_ROUTES', true),
    'route_prefix' => env('CAMPAIGNS_ROUTE_PREFIX', 'api'),
    'route_middleware' => ['api', 'auth:sanctum'],

    'campaigns_table' => env('CAMPAIGNS_TABLE', 'campaigns'),
    'campaign_events_table' => env('CAMPAIGN_EVENTS_TABLE', 'campaign_events'),

    // Event types that count as a conversion when an event is recorded without
    // an explicit is_conversion flag. Host-extendable; the `type` column is a
    // plain string so any channel can introduce new event types.
    'conversion_types' => ['conversion', 'purchase', 'signup'],
];
