<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Supplier;

class ItemMovementService
{
    /**
     * Get paginated movements for an item.
     *
     * @param  array{type?: string|null, sort_by?: string|null, sort_direction?: string|null}  $filters
     */
    public function getPaginatedMovements(Item $item, array $filters = []): LengthAwarePaginator
    {
        $query = $item->movements()
            ->with(['supplier', 'creator']);

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $sortBy = $filters['sort_by'] ?? 'movement_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $allowedSortFields = ['movement_date', 'quantity', 'total_price', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('movement_date', 'desc');
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate(20);
    }

    /**
     * Get all paginated movements for the current site.
     *
     * @param  array{type?: string|null, search?: string|null, item_id?: int|null, sort_by?: string|null, sort_direction?: string|null}  $filters
     */
    public function getAllPaginatedMovements(int $siteId, array $filters = []): LengthAwarePaginator
    {
        $query = ItemMovement::query()
            ->with(['item', 'item.site', 'supplier', 'creator']);

        // If not HQ, filter by current site
        if (!isOnHeadquarters()) {
            $query->whereHas('item', function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            });
        }

        // Filter by type
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by item
        if (! empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }

        // Search by item name or supplier name
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('item', function ($itemQ) use ($search) {
                    $itemQ->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('reference', 'ILIKE', "%{$search}%");
                })
                    ->orWhereHas('supplier', function ($itemQ) use ($search) {
                        $itemQ->where('name', 'ILIKE', "%{$search}%")
                            ->orWhere('description', 'ILIKE', "%{$search}%");
                    })
                    ->orWhere('supplier_name', 'ILIKE', "%{$search}%")
                    ->orWhere('supplier_invoice_number', 'ILIKE', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'movement_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $allowedSortFields = ['movement_date', 'quantity', 'total_price', 'created_at', 'type'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('movement_date', 'desc');
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate(20);
    }
}
