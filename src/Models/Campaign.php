<?php

namespace Whilesmart\Campaigns\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Whilesmart\Campaigns\Database\Factories\CampaignFactory;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign) {
            if (empty($campaign->slug)) {
                $campaign->slug = static::generateSlug($campaign);
            }
            if (empty($campaign->utm_campaign)) {
                $campaign->utm_campaign = $campaign->slug;
            }
        });
    }

    /**
     * Per-owner unique slug derived from the name, suffixed if taken.
     */
    public static function generateSlug(Campaign $campaign): string
    {
        $base = Str::slug((string) $campaign->name) ?: Str::lower(Str::random(8));

        $query = static::withTrashed()
            ->where('owner_type', $campaign->owner_type)
            ->where('owner_id', $campaign->owner_id);

        $slug = $base;
        $n = 1;
        while ((clone $query)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$n);
        }

        return $slug;
    }

    public function getTable(): string
    {
        return config('campaigns.campaigns_table', 'campaigns');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function events(): HasMany
    {
        return $this->hasMany(CampaignEvent::class);
    }

    /**
     * Record a single attributed event against this campaign. The one entry
     * point every channel uses (link click, email open, signup, conversion).
     *
     * @param  array<string, mixed>  $attrs  source, value, label, subject,
     *                                       visitor_hash, is_conversion,
     *                                       metadata, occurred_at
     */
    public function recordEvent(string $type, array $attrs = []): CampaignEvent
    {
        $conversionTypes = (array) config('campaigns.conversion_types', ['conversion']);

        $subject = $attrs['subject'] ?? null;

        return $this->events()->create([
            'type' => $type,
            'is_conversion' => $attrs['is_conversion'] ?? in_array($type, $conversionTypes, true),
            'source' => $attrs['source'] ?? null,
            'value' => $attrs['value'] ?? null,
            'label' => $attrs['label'] ?? null,
            'subject_type' => $subject?->getMorphClass() ?? ($attrs['subject_type'] ?? null),
            'subject_id' => $subject?->getKey() ?? ($attrs['subject_id'] ?? null),
            'visitor_hash' => $attrs['visitor_hash'] ?? null,
            'metadata' => $attrs['metadata'] ?? null,
            'occurred_at' => $attrs['occurred_at'] ?? now(),
        ]);
    }

    protected static function newFactory(): CampaignFactory
    {
        return CampaignFactory::new();
    }
}
