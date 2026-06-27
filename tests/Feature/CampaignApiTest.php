<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\Campaigns\Models\Campaign;

class CampaignApiTest extends TestCase
{
    private const OWNER = 'App\\Models\\Workspace';

    #[Test]
    public function it_creates_a_campaign_and_derives_a_slug(): void
    {
        $this->postJson('/api/campaigns', [
            'owner_type' => self::OWNER,
            'owner_id' => 1,
            'name' => 'Summer Creator Push',
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'summer-creator-push')
            ->assertJsonPath('data.utm_campaign', 'summer-creator-push')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('campaigns', [
            'owner_id' => 1,
            'name' => 'Summer Creator Push',
            'slug' => 'summer-creator-push',
        ]);
    }

    #[Test]
    public function it_suffixes_duplicate_slugs_per_owner(): void
    {
        Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Launch']);

        $this->postJson('/api/campaigns', [
            'owner_type' => self::OWNER,
            'owner_id' => 1,
            'name' => 'Launch',
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'launch-2');
    }

    #[Test]
    public function it_filters_campaigns_by_owner_and_search(): void
    {
        Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Black Friday']);
        Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Newsletter']);

        $this->getJson('/api/campaigns?owner_type='.urlencode(self::OWNER).'&owner_id=1&q=friday')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Black Friday');
    }

    #[Test]
    public function it_updates_a_campaign(): void
    {
        $campaign = Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Original']);

        $this->putJson("/api/campaigns/{$campaign->id}", ['name' => 'Renamed', 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed')
            ->assertJsonPath('data.is_active', false);
    }

    #[Test]
    public function it_soft_deletes_a_campaign(): void
    {
        $campaign = Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Temp']);

        $this->deleteJson("/api/campaigns/{$campaign->id}")->assertOk();

        $this->assertSoftDeleted('campaigns', ['id' => $campaign->id]);
    }
}
