<?php

namespace Whilesmart\Campaigns\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'type' => $this->type,
            'is_conversion' => (bool) $this->is_conversion,
            'source' => $this->source,
            'value' => $this->value !== null ? (float) $this->value : null,
            'label' => $this->label,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'visitor_hash' => $this->visitor_hash,
            'metadata' => $this->metadata,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
        ];
    }
}
