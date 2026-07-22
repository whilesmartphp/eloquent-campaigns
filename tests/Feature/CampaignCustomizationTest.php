<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\EnvelopeFormatter;
use Tests\Support\StampingHook;
use Tests\TestCase;
use Whilesmart\Campaigns\Models\Campaign;

class CampaignCustomizationTest extends TestCase
{
    private const OWNER = 'App\\Models\\Workspace';

    #[Test]
    public function a_host_can_reshape_the_response_envelope(): void
    {
        config(['campaigns.response_formatter' => EnvelopeFormatter::class]);

        $campaign = Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Reshaped']);

        $this->getJson("/api/campaigns/{$campaign->id}")
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('payload.name', 'Reshaped')
            ->assertJsonMissingPath('data');
    }

    #[Test]
    public function before_and_after_hooks_run_for_each_action(): void
    {
        config(['campaigns.middleware_hooks' => [StampingHook::class]]);

        Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Active']);
        Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Off', 'is_active' => false]);

        $this->getJson('/api/campaigns')
            ->assertOk()
            // after-hook stamped the response
            ->assertJsonPath('hooked', true)
            ->assertJsonPath('action', 'campaigns.index')
            // before-hook forced is_active=true, so only the active campaign shows
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Active');
    }
}
