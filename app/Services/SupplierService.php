<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use XetaSuite\Models\Supplier;
use XetaSuite\Services\Concerns\HasSearchAndSort;

class SupplierService
{
    use HasSearchAndSort;

    private const array SEARCH_COLUMNS = ['name', 'description'];

    private const array ALLOWED_SORTS = ['name', 'item_count', 'created_at'];

    private const array ITEM_SEARCH_COLUMNS = ['name', 'reference', 'description'];

    private const array ITEM_ALLOWED_SORTS = ['name', 'reference', 'current_price', 'created_at'];

    /**
     * Get a paginated list of suppliers with optional search and sorting.
     *
     * @param  array{search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedSuppliers(array $filters = []): LengthAwarePaginator
    {
        return Supplier::query()
            ->with('creator')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearchFilter($query, $search, self::SEARCH_COLUMNS))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySortFilter($query, $sortBy, $filters['sort_direction'] ?? 'asc', self::ALLOWED_SORTS),
                fn (Builder $query) => $query->orderBy('name')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get a paginated list of items for a specific supplier.
     *
     * @param  array{search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getSupplierItems(Supplier $supplier, array $filters = []): LengthAwarePaginator
    {
        return $supplier->items()
            ->with('site')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearchFilter($query, $search, self::ITEM_SEARCH_COLUMNS))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySortFilter($query, $sortBy, $filters['sort_direction'] ?? 'asc', self::ITEM_ALLOWED_SORTS),
                fn (Builder $query) => $query->orderBy('name')
            )
            ->paginate($filters['per_page'] ?? 20);
    }
}
