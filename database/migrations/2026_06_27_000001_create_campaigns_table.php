<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('campaigns.campaigns_table', 'campaigns'), function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            // Defaults pre-filled into a host's tracking-link / tagging UI.
            $table->string('utm_campaign')->nullable();
            $table->string('utm_medium')->nullable();

            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['owner_type', 'owner_id', 'slug']);
            $table->index(['owner_type', 'owner_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('campaigns.campaigns_table', 'campaigns'));
    }
};
