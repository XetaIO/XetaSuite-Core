<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use XetaSuite\Models\Supplier;

class SupplierService
{
    /**
     * Get a paginated list of suppliers with optional search and sorting.
     *
     * @param  array{search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedSuppliers(array $filters = []): LengthAwarePaginator
    {
        return Supplier::query()
            ->with('creator')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySorting($query, $sortBy, $filters['sort_direction'] ?? 'asc'),
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
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applyItemSearch($query, $search))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applyItemSorting($query, $sortBy, $filters['sort_direction'] ?? 'asc'),
                fn (Builder $query) => $query->orderBy('name')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Apply search filter to suppliers query.
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('description', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Apply sorting to suppliers query.
     */
    private function applySorting(Builder $query, string $sortBy, string $direction): Builder
    {
        $allowedSorts = ['name', 'item_count', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $direction === 'desc' ? 'desc' : 'asc');
        }

        return $query;
    }

    /**
     * Apply search filter to items query.
     */
    private function applyItemSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('description', 'ILIKE', "%{$search}%")
                ->orWhere('reference', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Apply sorting to items query.
     */
    private function applyItemSorting(Builder $query, string $sortBy, string $direction): Builder
    {
        $allowedSorts = ['name', 'description', 'reference', 'current_stock', 'current_price', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $direction === 'desc' ? 'desc' : 'asc');
        }

        return $query;
    }
}
