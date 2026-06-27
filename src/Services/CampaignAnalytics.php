<?php

namespace Whilesmart\Campaigns\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Whilesmart\Campaigns\Models\Campaign;
use Whilesmart\Campaigns\Models\CampaignEvent;

/**
 * Channel-agnostic attribution analytics over a campaign's events. Computes
 * engagement/conversion totals and a per-source ("which creator/segment
 * converts") breakdown straight from indexed columns, so it holds up for any
 * channel that records events: link clicks, email opens, ad impressions, ...
 */
class CampaignAnalytics
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Campaign $campaign, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $base = $this->scoped($campaign, $from, $to);

        $totals = (clone $base)->selectRaw(
            'COUNT(*) as total_events, '
            .'SUM(CASE WHEN is_conversion = 1 THEN 1 ELSE 0 END) as conversions, '
            .'COUNT(DISTINCT visitor_hash) as visitors, '
            .'COALESCE(SUM(CASE WHEN is_conversion = 1 THEN value ELSE 0 END), 0) as value'
        )->first();

        $totalEvents = (int) ($totals->total_events ?? 0);
        $conversions = (int) ($totals->conversions ?? 0);
        $engagements = $totalEvents - $conversions;

        return [
            'total_events' => $totalEvents,
            'engagements' => $engagements,
            'unique_visitors' => (int) ($totals->visitors ?? 0),
            'total_conversions' => $conversions,
            'conversion_rate' => $this->rate($conversions, $engagements),
            'total_value' => (float) ($totals->value ?? 0),
            'by_source' => $this->bySource($base),
            'by_type' => $this->byType($base),
            'by_day' => $this->byDay($base),
        ];
    }

    /**
     * Generic per-metadata-key breakdown (device, browser, os, country, ...).
     * Done in PHP so it stays correct across database JSON dialects.
     *
     * @return array<int, array{label: string, count: int}>
     */
    public function breakdownByMetadata(Campaign $campaign, string $key, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $counts = [];

        $this->scoped($campaign, $from, $to)
            ->select(['metadata'])
            ->lazy()
            ->each(function (CampaignEvent $event) use (&$counts, $key) {
                $label = $event->metadata[$key] ?? null;
                if ($label === null || $label === '') {
                    return;
                }
                $counts[$label] = ($counts[$label] ?? 0) + 1;
            });

        arsort($counts);

        return array_map(
            fn ($label, $count) => ['label' => (string) $label, 'count' => $count],
            array_keys($counts),
            array_values($counts),
        );
    }

    /**
     * @return Builder<CampaignEvent>
     */
    private function scoped(Campaign $campaign, ?Carbon $from, ?Carbon $to): Builder
    {
        $query = CampaignEvent::query()->where('campaign_id', $campaign->getKey());

        if ($from) {
            $query->where('occurred_at', '>=', $from);
        }
        if ($to) {
            $query->where('occurred_at', '<=', $to);
        }

        return $query;
    }

    /**
     * @param  Builder<CampaignEvent>  $base
     * @return array<int, array<string, mixed>>
     */
    private function bySource(Builder $base): array
    {
        return (clone $base)->selectRaw(
            "COALESCE(source, 'direct') as source, "
            .'COUNT(*) as events, '
            .'SUM(CASE WHEN is_conversion = 1 THEN 1 ELSE 0 END) as conversions, '
            .'COUNT(DISTINCT visitor_hash) as visitors, '
            .'COALESCE(SUM(CASE WHEN is_conversion = 1 THEN value ELSE 0 END), 0) as value'
        )
            ->groupBy('source')
            ->orderByDesc('events')
            ->get()
            ->map(function ($row) {
                $events = (int) $row->events;
                $conversions = (int) $row->conversions;
                $engagements = $events - $conversions;

                return [
                    'source' => $row->source,
                    'events' => $events,
                    'engagements' => $engagements,
                    'visitors' => (int) $row->visitors,
                    'conversions' => $conversions,
                    'value' => (float) $row->value,
                    'conversion_rate' => $this->rate($conversions, $engagements),
                ];
            })
            ->all();
    }

    /**
     * @param  Builder<CampaignEvent>  $base
     * @return array<int, array{type: string, count: int}>
     */
    private function byType(Builder $base): array
    {
        return (clone $base)->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['type' => (string) $row->type, 'count' => (int) $row->count])
            ->all();
    }

    /**
     * @param  Builder<CampaignEvent>  $base
     * @return array<int, array{date: string, count: int}>
     */
    private function byDay(Builder $base): array
    {
        return (clone $base)->selectRaw('DATE(occurred_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => (string) $row->date, 'count' => (int) $row->count])
            ->all();
    }

    private function rate(int $conversions, int $engagements): float
    {
        return $engagements > 0 ? round($conversions / $engagements * 100, 1) : 0.0;
    }
}
