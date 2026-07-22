<?php

namespace Whilesmart\Campaigns\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Whilesmart\Campaigns\Concerns\FormatsResponses;
use Whilesmart\Campaigns\Concerns\HasMiddlewareHooks;
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
    use FormatsResponses;
    use HasMiddlewareHooks;

    public function index(Request $request): JsonResponse
    {
        $request = $this->runBeforeHooks($request, 'campaigns.index');

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

        $response = $this->success(CampaignResource::collection($campaigns)->response()->getData(true));

        return $this->runAfterHooks($request, $response, 'campaigns.index');
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $request = $this->runBeforeHooks($request, 'campaigns.store');

        $campaign = Campaign::create($request->validated());

        $response = $this->success(new CampaignResource($campaign), 'Campaign created', 201);

        return $this->runAfterHooks($request, $response, 'campaigns.store');
    }

    public function show(Campaign $campaign, Request $request): JsonResponse
    {
        $request = $this->runBeforeHooks($request, 'campaigns.show');
        $this->authorizeAccessTo($campaign, $request->user());

        $response = $this->success(new CampaignResource($campaign->loadCount('events')));

        return $this->runAfterHooks($request, $response, 'campaigns.show');
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        $request = $this->runBeforeHooks($request, 'campaigns.update');
        $this->authorizeAccessTo($campaign, $request->user());
        $campaign->update($request->validated());

        $response = $this->success(new CampaignResource($campaign->fresh()->loadCount('events')), 'Campaign updated');

        return $this->runAfterHooks($request, $response, 'campaigns.update');
    }

    public function destroy(Campaign $campaign, Request $request): JsonResponse
    {
        $request = $this->runBeforeHooks($request, 'campaigns.destroy');
        $this->authorizeAccessTo($campaign, $request->user());
        $campaign->delete();

        $response = $this->success(null, 'Campaign deleted');

        return $this->runAfterHooks($request, $response, 'campaigns.destroy');
    }

    /**
     * Per-source attribution report. Optional ?from & ?to ISO dates.
     */
    public function analytics(Campaign $campaign, Request $request, CampaignAnalytics $analytics): JsonResponse
    {
        $request = $this->runBeforeHooks($request, 'campaigns.analytics');
        $this->authorizeAccessTo($campaign, $request->user());

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : null;
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : null;

        $response = $this->success($analytics->summary($campaign, $from, $to));

        return $this->runAfterHooks($request, $response, 'campaigns.analytics');
    }

    /**
     * Record a single attributed event for the campaign (any channel).
     */
    public function recordEvent(RecordEventRequest $request, Campaign $campaign): JsonResponse
    {
        $request = $this->runBeforeHooks($request, 'campaigns.recordEvent');
        $this->authorizeAccessTo($campaign, $request->user());

        $event = $campaign->recordEvent($request->input('type'), $request->safe()->except('type'));

        $response = $this->success(new CampaignEventResource($event), 'Event recorded', 201);

        return $this->runAfterHooks($request, $response, 'campaigns.recordEvent');
    }
}
