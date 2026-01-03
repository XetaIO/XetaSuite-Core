<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Services\Concerns\HasSearchAndSort;

class ItemMovementService
{
    use HasSearchAndSort;

    /**
     * Allowed sort fields for item movements.
     *
     * @var array<int, string>
     */
    private const ALLOWED_SORTS = ['movement_date', 'quantity', 'total_price', 'created_at', 'type'];

    /**
     * Get paginated movements for an item.
     *
     * @param  array{type?: string|null, sort_by?: string|null, sort_direction?: string|null}  $filters
     */
    public function getPaginatedMovements(Item $item, array $filters = []): LengthAwarePaginator
    {
        return $item->movements()
            ->with(['company', 'creator'])
            ->when(! empty($filters['type']), fn (Builder $query) => $query->where('type', $filters['type']))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySortFilter($query, $sortBy, $filters['sort_direction'] ?? 'desc', self::ALLOWED_SORTS, 'movement_date', 'desc'),
                fn (Builder $query) => $query->orderByDesc('movement_date')
            )
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    /**
     * Get all paginated movements for the current site.
     *
     * @param  array{type?: string|null, search?: string|null, item_id?: int|null, sort_by?: string|null, sort_direction?: string|null}  $filters
     */
    public function getAllPaginatedMovements(int $siteId, array $filters = []): LengthAwarePaginator
    {
        $query = ItemMovement::query()
            ->with(['item', 'item.site', 'company', 'creator']);

        // If not HQ, filter by item's site
        if (! isOnHeadquarters()) {
            $query->whereHas('item', fn (Builder $q) => $q->where('site_id', $siteId));
        }

        return $query
            ->when(! empty($filters['type']), fn (Builder $q) => $q->where('type', $filters['type']))
            ->when(! empty($filters['item_id']), fn (Builder $q) => $q->where('item_id', $filters['item_id']))
            ->when(! empty($filters['search']), fn (Builder $q) => $this->applyMovementSearch($q, $filters['search']))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $q, string $sortBy) => $this->applySortFilter($q, $sortBy, $filters['sort_direction'] ?? 'desc', self::ALLOWED_SORTS, 'movement_date', 'desc'),
                fn (Builder $q) => $q->orderByDesc('movement_date')
            )
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    /**
     * Apply search filter for movements.
     * This is complex due to searching across multiple relations.
     */
    private function applyMovementSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->whereHas('item', fn (Builder $itemQ) => $itemQ
                ->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('reference', 'ILIKE', "%{$search}%"))
                ->orWhereHas('company', fn (Builder $companyQ) => $companyQ
                    ->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%"))
                ->orWhere('company_name', 'ILIKE', "%{$search}%")
                ->orWhere('company_invoice_number', 'ILIKE', "%{$search}%");
        });
    }
}
