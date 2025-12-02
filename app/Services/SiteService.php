<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use XetaSuite\Models\Site;

class SiteService
{
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
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySorting($query, $sortBy, $filters['sort_direction'] ?? 'asc'),
                fn (Builder $query) => $query->orderByDesc('is_headquarters')->orderBy('name')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Check if a site can be deleted.
     */
    public function canDelete(Site $site): bool
    {
        // Cannot delete headquarters
        if ($site->is_headquarters) {
            return false;
        }

        // Cannot delete if site has zones
        return ! $site->zones()->exists();
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

    /**
     * Apply search filter to sites query.
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('city', 'LIKE', "%{$search}%")
                ->orWhere('address_line_1', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Apply sorting to sites query.
     */
    private function applySorting(Builder $query, string $sortBy, string $direction): Builder
    {
        $allowedSorts = ['name', 'city', 'is_headquarters', 'zones_count', 'users_count', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $direction === 'desc' ? 'desc' : 'asc');
        }

        return $query;
    }
}
