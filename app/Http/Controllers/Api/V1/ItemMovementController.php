<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\ItemMovements\CreateItemMovement;
use XetaSuite\Actions\ItemMovements\DeleteItemMovement;
use XetaSuite\Actions\ItemMovements\UpdateItemMovement;
use XetaSuite\Http\Requests\V1\ItemMovements\StoreItemMovementRequest;
use XetaSuite\Http\Requests\V1\ItemMovements\UpdateItemMovementRequest;
use XetaSuite\Http\Resources\V1\ItemMovements\ItemMovementResource;
use XetaSuite\Http\Resources\V1\ItemMovements\ItemMovementWithItemResource;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Services\ItemMovementService;

class ItemMovementController extends Controller
{
    public function __construct(
        private readonly ItemMovementService $movementService
    ) {
    }

    /**
     * Display a listing of item movements.
     * Only shows movements for items on the user's current site.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ItemMovement::class);

        $siteId = auth()->user()->current_site_id;

        $movements = $this->movementService->getAllPaginatedMovements($siteId, [
            'type' => request('type'),
            'search' => request('search'),
            'item_id' => request('item_id'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return ItemMovementWithItemResource::collection($movements);
    }

    /**
     * Store a new movement for an item.
     *
     * @param  StoreItemMovementRequest  $request  The incoming request.
     * @param  Item  $item  The item associated with the movement.
     * @param  CreateItemMovement  $action  The action to create the movement.
     */
    public function store(
        StoreItemMovementRequest $request,
        Item $item,
        CreateItemMovement $action
    ): ItemMovementResource {
        $movement = $action->handle($item, $request->user(), $request->validated());

        return new ItemMovementResource(
            $movement->load(['supplier', 'creator'])
        );
    }

    /**
     * Display the specified movement.
     *
     * @param  Item  $item  The item associated with the movement.
     * @param  ItemMovement  $movement  The movement to display.
     */
    public function show(Item $item, ItemMovement $movement): ItemMovementResource
    {
        $this->authorize('view', $movement);

        return new ItemMovementResource(
            $movement->load(['supplier', 'creator'])
        );
    }

    /**
     * Update the specified movement.
     *
     * @param  UpdateItemMovementRequest  $request  The incoming request.
     * @param  Item  $item  The item associated with the movement.
     * @param  ItemMovement  $movement  The movement to update.
     * @param  UpdateItemMovement  $action  The action to update the movement.
     */
    public function update(
        UpdateItemMovementRequest $request,
        Item $item,
        ItemMovement $movement,
        UpdateItemMovement $action
    ): ItemMovementResource {
        $movement = $action->handle($movement, $request->validated());

        return new ItemMovementResource(
            $movement->load(['supplier', 'creator'])
        );
    }

    /**
     * Delete the specified movement.
     *
     * @param  Item  $item  The item associated with the movement.
     * @param  ItemMovement  $movement  The movement to delete.
     * @param  DeleteItemMovement  $action  The action to delete the movement.
     */
    public function destroy(Item $item, ItemMovement $movement, DeleteItemMovement $action): JsonResponse
    {
        $this->authorize('delete', $movement);

        $result = $action->handle($movement);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return response()->json(null, 204);
    }
}
