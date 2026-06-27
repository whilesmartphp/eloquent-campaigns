<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('campaigns.campaign_events_table', 'campaign_events'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')
                ->constrained(config('campaigns.campaigns_table', 'campaigns'))
                ->cascadeOnDelete();

            // Host-defined event type (click, open, impression, signup, conversion, ...).
            $table->string('type');
            $table->boolean('is_conversion')->default(false);

            // The universal attribution axis: a creator handle, utm_source,
            // audience segment, or any channel's notion of "who drove this".
            $table->string('source')->nullable();

            // Optional commercial weight (order value, plan tier, ...).
            $table->decimal('value', 12, 2)->nullable();
            $table->string('label')->nullable();

            // Optional pointer to whatever triggered the event (a Link, a User,
            // an email recipient). Channel-agnostic, so nullable.
            $table->nullableMorphs('subject');

            // Opaque per-actor identifier supplied by the host: a hashed IP+UA
            // for web, a recipient id for email, a device id for push. Powers
            // unique counts and click-to-conversion matching. The package never
            // computes it, so it makes no assumption about the channel.
            $table->string('visitor_hash', 64)->nullable();

            // Channel-specific attributes: device/browser/os/country/referrer for
            // web, email_client for email, etc. Kept flexible so the schema stays
            // channel-agnostic.
            $table->json('metadata')->nullable();

            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['campaign_id', 'type', 'occurred_at']);
            $table->index(['campaign_id', 'source']);
            $table->index(['campaign_id', 'is_conversion']);
            $table->index('visitor_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('campaigns.campaign_events_table', 'campaign_events'));
    }
};
