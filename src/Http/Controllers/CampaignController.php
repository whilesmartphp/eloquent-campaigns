<?php

namespace Whilesmart\Campaigns\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Whilesmart\Campaigns\Http\Requests\RecordEventRequest;
use Whilesmart\Campaigns\Http\Requests\StoreCampaignRequest;
use Whilesmart\Campaigns\Http\Requests\UpdateCampaignRequest;
use Whilesmart\Campaigns\Http\Resources\CampaignEventResource;
use Whilesmart\Campaigns\Http\Resources\CampaignResource;
use Whilesmart\Campaigns\Models\Campaign;
use Whilesmart\Campaigns\Services\CampaignAnalytics;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerController;

class CampaignController extends Controller
{
    use AuthorizesOwnerController;

    public function index(Request $request): JsonResponse
    {
        $query = $this->scopeAccessibleOwners(Campaign::query(), $request->user())
            ->withCount('events');

        if ($request->filled('owner_type') && $request->filled('owner_id')) {
            $query->where('owner_type', $request->input('owner_type'))
                ->where('owner_id', $request->input('owner_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('q')) {
            $term = '%'.strtolower($request->input('q')).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('lower(name) like ?', [$term])
                    ->orWhereRaw('lower(slug) like ?', [$term]);
            });
        }

        $campaigns = $query->orderByDesc('updated_at')
            ->paginate((int) $request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => CampaignResource::collection($campaigns)->response()->getData(true),
        ]);
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = Campaign::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => new CampaignResource($campaign),
        ], 201);
    }

    public function show(Campaign $campaign, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($campaign, $request->user());

        return response()->json([
            'success' => true,
            'data' => new CampaignResource($campaign->loadCount('events')),
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        $this->authorizeAccessTo($campaign, $request->user());
        $campaign->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => new CampaignResource($campaign->fresh()->loadCount('events')),
        ]);
    }

    public function destroy(Campaign $campaign, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($campaign, $request->user());
        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted.',
        ]);
    }

    /**
     * Per-source attribution report. Optional ?from & ?to ISO dates.
     */
    public function analytics(Campaign $campaign, Request $request, CampaignAnalytics $analytics): JsonResponse
    {
        $this->authorizeAccessTo($campaign, $request->user());

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : null;
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : null;

        return response()->json([
            'success' => true,
            'data' => $analytics->summary($campaign, $from, $to),
        ]);
    }

    /**
     * Record a single attributed event for the campaign (any channel).
     */
    public function recordEvent(RecordEventRequest $request, Campaign $campaign): JsonResponse
    {
        $this->authorizeAccessTo($campaign, $request->user());

        $event = $campaign->recordEvent($request->input('type'), $request->safe()->except('type'));

        return response()->json([
            'success' => true,
            'data' => new CampaignEventResource($event),
        ], 201);
    }
}
