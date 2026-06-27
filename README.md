# whilesmart/eloquent-campaigns

Channel-agnostic campaign attribution for Laravel. Group activity into owner-scoped **campaigns**, record **source-tagged events** (a creator's link click, an email open, an ad impression, a signup, a purchase), and report **per-source performance** including conversions and value.

A campaign is just a named, owner-scoped initiative. What feeds it is up to the host, so the same package powers:

- tracking which **creators** convert from their links,
- a **marketing campaign** on a business dashboard,
- an **email re-engagement** blast to inactive users.

Each campaign is scoped to an owner (workspace, organization, team, user) through [`whilesmart/eloquent-owner-access`](https://github.com/whilesmartphp/eloquent-owner-access).

## Install

```
composer require whilesmart/eloquent-campaigns
php artisan migrate
```

Routes register automatically under the `api` prefix with `auth:sanctum`. Set `CAMPAIGNS_REGISTER_ROUTES=false` to mount them yourself. Bind your own `OwnerAuthorizer` to enforce who can see which owner's campaigns (the default allows all authenticated requests).

## Owning model

```php
use Whilesmart\Campaigns\Traits\HasCampaigns;

class Workspace extends Model
{
    use HasCampaigns;
}

$campaign = $workspace->campaigns()->create(['name' => 'Summer Creator Push']);
```

## Record events from any channel

```php
// A creator's link click (web)
$campaign->recordEvent('click', [
    'source' => 'jane_creator',
    'visitor_hash' => $signature,
    'subject' => $link,
    'metadata' => ['device' => 'mobile', 'country' => 'US'],
]);

// A conversion ($ value attributed back to the source that drove it)
$campaign->recordEvent('conversion', ['source' => 'jane_creator', 'value' => 49.99]);

// An email open / signup (no clicks at all)
$campaign->recordEvent('open', ['source' => 'inactive_30d']);
$campaign->recordEvent('signup', ['source' => 'inactive_30d']); // counts as a conversion
```

`type` is a plain string, so any channel can define its own events. Which types count as conversions is configurable (`conversion_types`).

`visitor_hash` is an **opaque unique-actor id you supply** — a hashed IP+UA for web traffic, a recipient id for email, a device id for push. The package never computes it, so it makes no assumption about the channel; it only uses it for unique counts and to match a conversion back to the actor's earlier event.

## Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/api/campaigns` | List (filter by `is_active`, `q`, owner) |
| POST | `/api/campaigns` | Create |
| GET | `/api/campaigns/{campaign}` | Show |
| PUT/PATCH | `/api/campaigns/{campaign}` | Update |
| DELETE | `/api/campaigns/{campaign}` | Soft delete |
| GET | `/api/campaigns/{campaign}/analytics` | Per-source attribution report (`?from`, `?to`) |
| POST | `/api/campaigns/{campaign}/events` | Record an event |

## Analytics shape

```json
{
  "total_events": 4, "engagements": 3, "unique_visitors": 3,
  "total_conversions": 1, "conversion_rate": 33.3, "total_value": 49.99,
  "by_source": [
    { "source": "jane", "events": 3, "engagements": 2, "visitors": 2, "conversions": 1, "value": 49.99, "conversion_rate": 50 }
  ],
  "by_type": [{ "type": "click", "count": 3 }],
  "by_day": [{ "date": "2026-06-27", "count": 4 }]
}
```

## License

MIT © WhileSmart LTD
