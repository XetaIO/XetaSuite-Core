<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use XetaSuite\Models\Site;
use XetaSuite\Services\Concerns\HasSearchAndSort;

class SiteService
{
    use HasSearchAndSort;

    private const array SEARCH_COLUMNS = ['name', 'email', 'city', 'address'];

    private const array ALLOWED_SORTS = ['name', 'city', 'is_headquarters', 'zones_count', 'users_count', 'created_at'];

    /**
     * Get a paginated list of sites with optional search and sorting.
     *
     * @param  array{search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedSites(array $filters = []): LengthAwarePaginator
    {
        return Site::query()
            ->with('managers', 'users')
            ->withCount(['zones', 'users'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearchFilter($query, $search, self::SEARCH_COLUMNS))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySortFilter($query, $sortBy, $filters['sort_direction'] ?? 'asc', self::ALLOWED_SORTS),
                fn (Builder $query) => $query->orderByDesc('is_headquarters')->orderBy('name')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Check if a headquarters site already exists.
     */
    public function headquartersExists(?int $excludeId = null): bool
    {
        return Site::query()
            ->where('is_headquarters', true)
            ->when($excludeId, fn (Builder $query) => $query->where('id', '!=', $excludeId))
            ->exists();
    }

}
