<?php

namespace Whilesmart\Campaigns\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Whilesmart\Campaigns\Models\Campaign;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_type' => 'App\\Models\\Workspace',
            'owner_id' => 1,
            'name' => ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
