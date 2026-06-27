<?php

namespace Whilesmart\Campaigns\Enums;

/**
 * Suggested event types across channels. The `type` column stores a plain
 * string, so host apps can introduce new event types (web clicks, email
 * opens, ad impressions, signups, purchases) without a schema or enum change.
 */
enum CampaignEventType: string
{
    case Impression = 'impression';
    case Click = 'click';
    case Open = 'open';
    case Signup = 'signup';
    case Purchase = 'purchase';
    case Conversion = 'conversion';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
