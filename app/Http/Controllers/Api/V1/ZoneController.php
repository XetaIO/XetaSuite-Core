<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Zones\CreateZone;
use XetaSuite\Actions\Zones\DeleteZone;
use XetaSuite\Actions\Zones\UpdateZone;
use XetaSuite\Http\Requests\V1\Zones\StoreZoneRequest;
use XetaSuite\Http\Requests\V1\Zones\UpdateZoneRequest;
use XetaSuite\Http\Resources\V1\Zones\ZoneDetailResource;
use XetaSuite\Http\Resources\V1\Zones\ZoneResource;
use XetaSuite\Models\Zone;
use XetaSuite\Services\ZoneService;

class ZoneController extends Controller
{
    public function __construct(
        private readonly ZoneService $zoneService
    ) {
    }

    /**
     * Display a listing of zones.
     * Only shows zones for the user's current site.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Zone::class);

        $zones = $this->zoneService->getPaginatedZones([
            'site_id' => auth()->user()->current_site_id,
            'parent_id' => request()->has('parent_id') ? request('parent_id') : null,
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return ZoneResource::collection($zones);
    }

    /**
     * Store a newly created zone.
     *
     * @param  StoreZoneRequest  $request  The incoming request.
     * @param  CreateZone  $action  The action to create the zone.
     */
    public function store(StoreZoneRequest $request, CreateZone $action): ZoneDetailResource
    {
        $zone = $action->handle($request->validated());

        return new ZoneDetailResource(
            $zone->load(['site', 'parent', 'children'])
                ->loadCount(['children', 'materials'])
        );
    }

    /**
     * Display the specified zone.
     *
     * @param  Zone  $zone  The zone to display.
     */
    public function show(Zone $zone): ZoneDetailResource
    {
        $this->authorize('view', $zone);

        $zone->load(['site', 'parent']);
        $zone->loadCount(['children', 'materials']);

        // Load children with counts if this is a parent zone
        if (! $zone->allow_material) {
            $zone->load(['children' => function ($query) {
                $query->withCount(['children', 'materials']);
            }]);
        } else {
            // Load materials if this zone allows materials
            $zone->load('materials');
        }

        return new ZoneDetailResource($zone);
    }

    /**
     * Update the specified zone.
     *
     * @param  UpdateZoneRequest  $request  The incoming request.
     * @param  Zone  $zone  The zone to update.
     * @param  UpdateZone  $action  The action to update the zone.
     */
    public function update(UpdateZoneRequest $request, Zone $zone, UpdateZone $action): ZoneDetailResource
    {
        $zone = $action->handle($zone, $request->validated());

        return new ZoneDetailResource(
            $zone->load(['site', 'parent', 'children'])
                ->loadCount(['children', 'materials'])
        );
    }

    /**
     * Delete the specified zone.
     *
     * @param  Zone  $zone  The zone to delete.
     * @param  DeleteZone  $action  The action to delete the zone.
     */
    public function destroy(Zone $zone, DeleteZone $action): JsonResponse
    {
        $this->authorize('delete', $zone);

        $result = $action->handle($zone);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return response()->json(null, 204);
    }

    /**
     * Get available parent zones for the user's current site.
     */
    public function availableParents(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Zone::class);

        $siteId = auth()->user()->current_site_id;
        $excludeZoneId = request('exclude_zone_id');

        $excludeZone = $excludeZoneId ? Zone::find($excludeZoneId) : null;

        $zones = $this->zoneService->getAvailableParentZones($siteId, $excludeZone);

        return ZoneResource::collection($zones);
    }

    /**
     * Get child zones for a zone.
     *
     * @param  Zone  $zone  The zone to get children for.
     */
    public function children(Zone $zone): AnonymousResourceCollection
    {
        $this->authorize('view', $zone);

        $children = $this->zoneService->getChildZones($zone);

        return ZoneResource::collection($children);
    }

    /**
     * Get materials for a zone.
     *
     * @param  Zone  $zone  The zone to get materials for.
     * @return JsonResponse
     */
    public function materials(Zone $zone): JsonResponse|AnonymousResourceCollection
    {
        $this->authorize('view', $zone);

        if (! $zone->allow_material) {
            return response()->json([
                'message' => __('zones.materials_not_allowed'),
            ], 422);
        }

        $materials = $zone->materials()
            ->orderBy('name')
            ->get();

        // Return simple material data for now
        return response()->json([
            'data' => $materials->map(fn ($material) => [
                'id' => $material->id,
                'name' => $material->name,
                'description' => $material->description,
            ]),
        ]);
    }
}
