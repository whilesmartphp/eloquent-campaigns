<?php

namespace Whilesmart\Campaigns\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Whilesmart\Campaigns\Models\Campaign;
use Whilesmart\Campaigns\Models\CampaignEvent;

class CampaignEventFactory extends Factory
{
    protected $model = CampaignEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'type' => 'click',
            'is_conversion' => false,
            'source' => $this->faker->randomElement(['email', 'twitter', 'newsletter', 'direct']),
            'visitor_hash' => $this->faker->sha256(),
            'occurred_at' => now(),
        ];
    }

    public function conversion(float $value = 49.99): static
    {
        return $this->state(fn () => [
            'type' => 'conversion',
            'is_conversion' => true,
            'value' => $value,
        ]);
    }
}
