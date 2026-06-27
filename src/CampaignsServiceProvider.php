<?php

namespace Whilesmart\Campaigns;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CampaignsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/campaigns.php', 'campaigns');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/campaigns.php' => config_path('campaigns.php'),
        ], 'campaigns-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'campaigns-migrations');

        if (config('campaigns.register_routes', true)) {
            Route::middleware(config('campaigns.route_middleware', ['api', 'auth:sanctum']))
                ->prefix(config('campaigns.route_prefix', 'api'))
                ->group(__DIR__.'/../routes/api.php');
        }
    }
}
