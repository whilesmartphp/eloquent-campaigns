<?php

namespace Whilesmart\Campaigns\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Whilesmart\Campaigns\Models\Campaign;

trait HasCampaigns
{
    public function campaigns(): MorphMany
    {
        return $this->morphMany(Campaign::class, 'owner');
    }
}
