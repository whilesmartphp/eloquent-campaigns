<?php

namespace Whilesmart\Campaigns\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Whilesmart\Campaigns\Database\Factories\CampaignEventFactory;

class CampaignEvent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_conversion' => 'boolean',
        'value' => 'decimal:2',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('campaigns.campaign_events_table', 'campaign_events');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): CampaignEventFactory
    {
        return CampaignEventFactory::new();
    }
}
