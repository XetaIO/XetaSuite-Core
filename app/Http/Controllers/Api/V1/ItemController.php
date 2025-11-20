<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Http\Resources\V1\Items\ItemDashboardResource;
use XetaSuite\Http\Resources\V1\Items\ItemDetailResource;
use XetaSuite\Http\Resources\V1\Items\ItemResource;
use XetaSuite\Models\Item;

/**
 * Endpoint                                   Resource                              Données retournées
 *
 * GET /api/items                        ItemResource                      Minimalist list
 * GET /api/items/{id}                ItemDetailResource            All the details
 * GET /api/dashboard/items    ItemDashboardResource    Alerts + key stats
 */
class ItemController extends Controller
{
    /**
     * Display a listing of items (minimal data).
     */
    public function index(): AnonymousResourceCollection
    {
        $items = Item::with('site')
            ->paginate(20);

        return ItemResource::collection($items);
    }

    /**
     * Display the specified item (full details).
     */
    public function show(Item $item): ItemDetailResource
    {
        $item->load([
            'site',
            'supplier',
            'createdBy',
            'prices' => fn ($q) => $q->limit(10),
            'movements' => fn ($q) => $q->limit(20),
            'materials',
        ]);

        return new ItemDetailResource($item);
    }

    /**
     * Get items for the dashboard (alerts + stats).
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
