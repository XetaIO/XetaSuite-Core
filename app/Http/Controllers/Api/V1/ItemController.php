<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Items\CreateItem;
use XetaSuite\Actions\Items\CreateItemMovement;
use XetaSuite\Actions\Items\DeleteItem;
use XetaSuite\Actions\Items\UpdateItem;
use XetaSuite\Http\Requests\V1\Items\StoreItemMovementRequest;
use XetaSuite\Http\Requests\V1\Items\StoreItemRequest;
use XetaSuite\Http\Requests\V1\Items\UpdateItemRequest;
use XetaSuite\Http\Resources\V1\Items\ItemDashboardResource;
use XetaSuite\Http\Resources\V1\Items\ItemDetailResource;
use XetaSuite\Http\Resources\V1\Items\ItemMovementResource;
use XetaSuite\Http\Resources\V1\Items\ItemResource;
use XetaSuite\Models\Item;
use XetaSuite\Services\ItemService;

class ItemController extends Controller
{
    public function __construct(
        private readonly ItemService $itemService
    ) {
    }

    /**
     * Display a listing of items.
     * Only shows items on the user's current site.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Item::class);

        $items = $this->itemService->getPaginatedItems([
            'search' => request('search'),
            'supplier_id' => request('supplier_id'),
            'stock_status' => request('stock_status'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return ItemResource::collection($items);
    }

    /**
     * Store a newly created item.
     */
    public function store(StoreItemRequest $request, CreateItem $action): ItemDetailResource
    {
        $item = $action->handle($request->user(), $request->validated());

        return new ItemDetailResource(
            $item->load(['supplier', 'creator', 'materials', 'recipients'])
        );
    }

    /**
     * Display the specified item.
     */
    public function show(Item $item): ItemDetailResource
    {
        $this->authorize('view', $item);

        $item->load(['supplier', 'creator', 'editor', 'materials', 'recipients']);

        return new ItemDetailResource($item);
    }

    /**
     * Update the specified item.
     */
    public function update(UpdateItemRequest $request, Item $item, UpdateItem $action): ItemDetailResource
    {
        $item = $action->handle($item, $request->user(), $request->validated());

        return new ItemDetailResource(
            $item->load(['supplier', 'creator', 'editor', 'materials', 'recipients'])
        );
    }

    /**
     * Remove the specified item from storage.
     */
    public function destroy(Item $item, DeleteItem $action): JsonResponse
    {
        $this->authorize('delete', $item);

        $result = $action->handle($item);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return response()->json(null, 204);
    }

    /**
     * Get monthly statistics for an item.
     */
    public function stats(Item $item): JsonResponse
    {
        $this->authorize('view', $item);

        $stats = $this->itemService->getMonthlyStats($item);

        return response()->json([
            'stats' => $stats,
        ]);
    }

    /**
     * Get movements for an item.
     */
    public function movements(Item $item): AnonymousResourceCollection
    {
        $this->authorize('view', $item);

        $movements = $item->movements()
            ->with(['supplier', 'creator'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return ItemMovementResource::collection($movements);
    }

    /**
     * Store a new movement for an item.
     */
    public function storeMovement(
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
     * Generate QR code for an item.
     */
    public function qrCode(Item $item): JsonResponse
    {
        $this->authorize('view', $item);

        $size = (int) request('size', 200);
        $size = max(100, min(500, $size)); // Limit between 100 and 500

        $url = config('app.frontend_url').'/items/'.$item->id;

        $qrCode = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        return response()->json([
            'data' => [
                'svg' => base64_encode($result->getString()),
                'url' => $url,
                'size' => $size,
            ],
        ]);
    }

    /**
     * Get available suppliers for item creation.
     */
    public function availableSuppliers(): JsonResponse
    {
        $this->authorize('viewAny', Item::class);

        $suppliers = $this->itemService->getAvailableSuppliers();

        return response()->json([
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Get available materials for item assignment.
     */
    public function availableMaterials(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Item::class);

        $materials = $this->itemService->getAvailableMaterials($request->query('search'));

        return response()->json([
            'materials' => $materials,
        ]);
    }

    /**
     * Get available recipients for critical alerts.
     */
    public function availableRecipients(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Item::class);

        $recipients = $this->itemService->getAvailableRecipients($request->query('search'));

        return response()->json([
            'recipients' => $recipients,
        ]);
    }

    /**
     * Dashboard items with low stock alerts.
     */
    public function dashboard(): AnonymousResourceCollection
    {
        $items = Item::with('site')
            ->where(function ($query) {
                $query->where('number_warning_enabled', true)
                    ->whereRaw('(item_entry_total - item_exit_total) <= number_warning_minimum');
            })
            ->orWhere(function ($query) {
                $query->where('number_critical_enabled', true)
                    ->whereRaw('(item_entry_total - item_exit_total) <= number_critical_minimum');
            })
            ->orderBy('item_entry_total', 'asc')
            ->orderBy('item_exit_total', 'desc')
            ->limit(50)
            ->get();

        return ItemDashboardResource::collection($items);
    }
}
