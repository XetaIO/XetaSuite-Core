<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Materials\CreateMaterial;
use XetaSuite\Actions\Materials\DeleteMaterial;
use XetaSuite\Actions\Materials\UpdateMaterial;
use XetaSuite\Http\Requests\V1\Materials\StoreMaterialRequest;
use XetaSuite\Http\Requests\V1\Materials\UpdateMaterialRequest;
use XetaSuite\Http\Resources\V1\Materials\MaterialDetailResource;
use XetaSuite\Http\Resources\V1\Materials\MaterialResource;
use XetaSuite\Models\Material;
use XetaSuite\Services\MaterialService;

class MaterialController extends Controller
{
    public function __construct(
        private readonly MaterialService $materialService
    ) {}

    /**
     * Display a listing of materials.
     * Only shows materials for zones on the user's current site.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Material::class);

        $materials = $this->materialService->getPaginatedMaterials([
            'zone_id' => request('zone_id'),
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return MaterialResource::collection($materials);
    }

    /**
     * Store a newly created material.
     */
    public function store(StoreMaterialRequest $request, CreateMaterial $action): MaterialDetailResource
    {
        $material = $action->handle($request->user(), $request->validated());

        return new MaterialDetailResource(
            $material->load(['zone', 'creator', 'recipients'])
        );
    }

    /**
     * Display the specified material.
     */
    public function show(Material $material): MaterialDetailResource
    {
        $this->authorize('view', $material);

        $material->load(['zone', 'creator', 'recipients']);

        return new MaterialDetailResource($material);
    }

    /**
     * Update the specified material.
     */
    public function update(UpdateMaterialRequest $request, Material $material, UpdateMaterial $action): MaterialDetailResource
    {
        $material = $action->handle($material, $request->validated());

        return new MaterialDetailResource(
            $material->load(['zone', 'creator', 'recipients'])
        );
    }

    /**
     * Remove the specified material.
     */
    public function destroy(Material $material, DeleteMaterial $action): JsonResponse
    {
        $this->authorize('delete', $material);

        $action->handle($material);

        return response()->json(null, 204);
    }

    /**
     * Get available zones for material creation/update.
     * Only zones that allow materials on user's current site.
     */
    public function availableZones(): JsonResponse
    {
        $this->authorize('viewAny', Material::class);

        $zones = $this->materialService->getAvailableZones();

        return response()->json([
            'data' => $zones->map(fn ($zone) => [
                'id' => $zone->id,
                'name' => $zone->name,
            ]),
        ]);
    }

    /**
     * Get available recipients for cleaning alerts.
     * Users with access to the current site.
     */
    public function availableRecipients(): JsonResponse
    {
        $this->authorize('viewAny', Material::class);

        $recipients = $this->materialService->getAvailableRecipients();

        return response()->json([
            'data' => $recipients->map(fn ($user) => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
            ]),
        ]);
    }

    /**
     * Get monthly statistics for a material over the last 12 months.
     * Returns counts for incidents, maintenances, cleanings, and item movements (exit).
     */
    public function stats(Material $material): JsonResponse
    {
        $this->authorize('view', $material);

        $stats = $this->materialService->getMonthlyStats($material);

        return response()->json(['data' => $stats]);
    }
}
