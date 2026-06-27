<?php

namespace Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\Campaigns\Models\Campaign;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class CampaignAuthorizationTest extends TestCase
{
    private const OWNER = 'App\\Models\\Workspace';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(OwnerAuthorizer::class, new class implements OwnerAuthorizer
        {
            public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool
            {
                return false;
            }

            public function scope(Builder $query, ?Authenticatable $user, string $ownerTypeColumn = 'owner_type', string $ownerIdColumn = 'owner_id'): Builder
            {
                return $query->whereRaw('0 = 1');
            }
        });
    }

    #[Test]
    public function store_is_forbidden_when_authorizer_denies(): void
    {
        $this->postJson('/api/campaigns', [
            'owner_type' => self::OWNER,
            'owner_id' => 1,
            'name' => 'Hijacked',
        ])->assertForbidden();

        $this->assertDatabaseCount('campaigns', 0);
    }

    #[Test]
    public function show_update_destroy_and_actions_are_forbidden_when_authorizer_denies(): void
    {
        $campaign = Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Private']);

        $this->getJson("/api/campaigns/{$campaign->id}")->assertForbidden();
        $this->putJson("/api/campaigns/{$campaign->id}", ['name' => 'Hijacked'])->assertForbidden();
        $this->getJson("/api/campaigns/{$campaign->id}/analytics")->assertForbidden();
        $this->postJson("/api/campaigns/{$campaign->id}/events", ['type' => 'click'])->assertForbidden();
        $this->deleteJson("/api/campaigns/{$campaign->id}")->assertForbidden();

        $this->assertSame('Private', $campaign->fresh()->name);
    }

    #[Test]
    public function index_returns_nothing_when_scope_denies(): void
    {
        Campaign::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'name' => 'Private']);

        $this->getJson('/api/campaigns')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 0);
    }
}
