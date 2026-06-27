<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\Campaigns\Models\Campaign;

class CampaignAnalyticsTest extends TestCase
{
    private const OWNER = 'App\\Models\\Workspace';

    private function record(int $campaignId, array $payload): void
    {
        $this->postJson("/api/campaigns/{$campaignId}/events", $payload)->assertCreated();
    }

    #[Test]
    public function it_attributes_clicks_and_conversions_per_creator(): void
    {
        $campaign = Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Creator Push']);

        // Jane: 2 clicks, 1 conversion ($49.99). Bob: 1 click, no conversion.
        $this->record($campaign->id, ['type' => 'click', 'source' => 'jane', 'visitor_hash' => 'h1']);
        $this->record($campaign->id, ['type' => 'click', 'source' => 'jane', 'visitor_hash' => 'h2']);
        $this->record($campaign->id, ['type' => 'conversion', 'source' => 'jane', 'value' => 49.99, 'visitor_hash' => 'h2']);
        $this->record($campaign->id, ['type' => 'click', 'source' => 'bob', 'visitor_hash' => 'h3']);

        $this->getJson("/api/campaigns/{$campaign->id}/analytics")
            ->assertOk()
            ->assertJsonPath('data.total_events', 4)
            ->assertJsonPath('data.engagements', 3)
            ->assertJsonPath('data.total_conversions', 1)
            ->assertJsonPath('data.unique_visitors', 3)
            ->assertJsonPath('data.total_value', 49.99)
            ->assertJsonPath('data.by_source.0.source', 'jane')
            ->assertJsonPath('data.by_source.0.engagements', 2)
            ->assertJsonPath('data.by_source.0.conversions', 1)
            ->assertJsonPath('data.by_source.1.source', 'bob')
            ->assertJsonPath('data.by_source.1.conversions', 0);
    }

    #[Test]
    public function it_works_for_a_non_link_channel_like_email(): void
    {
        $campaign = Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Win-back Email']);

        // No clicks at all: email opens are engagements, signups are conversions
        // (signup is in the configured conversion_types). Proves the engine is
        // not link-shaped.
        $this->record($campaign->id, ['type' => 'open', 'source' => 'inactive_30d', 'visitor_hash' => 'e1']);
        $this->record($campaign->id, ['type' => 'open', 'source' => 'inactive_30d', 'visitor_hash' => 'e2']);
        $this->record($campaign->id, ['type' => 'signup', 'source' => 'inactive_30d', 'visitor_hash' => 'e1']);

        $this->getJson("/api/campaigns/{$campaign->id}/analytics")
            ->assertOk()
            ->assertJsonPath('data.total_events', 3)
            ->assertJsonPath('data.engagements', 2)
            ->assertJsonPath('data.total_conversions', 1)
            ->assertJsonPath('data.by_source.0.source', 'inactive_30d')
            ->assertJsonPath('data.by_source.0.conversions', 1);
    }

    #[Test]
    public function record_event_model_method_sets_conversion_from_type(): void
    {
        $campaign = Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Direct']);

        $click = $campaign->recordEvent('click', ['source' => 'newsletter']);
        $purchase = $campaign->recordEvent('purchase', ['source' => 'newsletter', 'value' => 12.50]);

        $this->assertFalse($click->is_conversion);
        $this->assertTrue($purchase->is_conversion);
        $this->assertSame('newsletter', $purchase->source);
    }
}
