<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Cleanings\CreateCleaning;
use XetaSuite\Actions\Cleanings\DeleteCleaning;
use XetaSuite\Actions\Cleanings\UpdateCleaning;
use XetaSuite\Http\Requests\V1\Cleanings\StoreCleaningRequest;
use XetaSuite\Http\Requests\V1\Cleanings\UpdateCleaningRequest;
use XetaSuite\Http\Resources\V1\Cleanings\CleaningDetailResource;
use XetaSuite\Http\Resources\V1\Cleanings\CleaningResource;
use XetaSuite\Models\Cleaning;
use XetaSuite\Services\CleaningService;

class CleaningController extends Controller
{
    public function __construct(
        private readonly CleaningService $cleaningService
    ) {
    }

    /**
     * Display a listing of cleanings.
     * Only shows cleanings for the user's current site.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Cleaning::class);

        $cleanings = $this->cleaningService->getPaginatedCleanings([
            'material_id' => request('material_id'),
            'type' => request('type'),
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return CleaningResource::collection($cleanings);
    }

    /**
     * Store a newly created cleaning.
     */
    public function store(StoreCleaningRequest $request, CreateCleaning $action): CleaningDetailResource
    {
        $cleaning = $action->handle($request->user(), $request->validated());

        return new CleaningDetailResource($cleaning);
    }

    /**
     * Display the specified cleaning.
     */
    public function show(Cleaning $cleaning): CleaningDetailResource
    {
        $this->authorize('view', $cleaning);

        $cleaning->load([
            'material.zone',
            'creator',
            'editor',
            'site',
        ]);

        return new CleaningDetailResource($cleaning);
    }

    /**
     * Update the specified cleaning.
     */
    public function update(UpdateCleaningRequest $request, Cleaning $cleaning, UpdateCleaning $action): CleaningDetailResource
    {
        $cleaning = $action->handle($cleaning, $request->user(), $request->validated());

        return new CleaningDetailResource($cleaning);
    }

    /**
     * Delete the specified cleaning.
     */
    public function destroy(Cleaning $cleaning, DeleteCleaning $action): JsonResponse
    {
        $this->authorize('delete', $cleaning);

        $action->handle($cleaning);

        return response()->json(null, 204);
    }

    /**
     * Get available materials for cleaning creation.
     */
    public function availableMaterials(): JsonResponse
    {
        $this->authorize('viewAny', Cleaning::class);

        return response()->json([
            'data' => $this->cleaningService->getAvailableMaterials(),
        ]);
    }

    /**
     * Get available type options.
     */
    public function typeOptions(): JsonResponse
    {
        return response()->json([
            'data' => $this->cleaningService->getTypeOptions(),
        ]);
    }
}
