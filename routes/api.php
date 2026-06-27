<?php

use Illuminate\Support\Facades\Route;
use Whilesmart\Campaigns\Http\Controllers\CampaignController;

Route::apiResource('campaigns', CampaignController::class);
Route::get('campaigns/{campaign}/analytics', [CampaignController::class, 'analytics']);
Route::post('campaigns/{campaign}/events', [CampaignController::class, 'recordEvent']);
