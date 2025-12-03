<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use XetaSuite\Models\Zone;

class ZoneService
{
    /**
     * Get a paginated list of zones with optional search and sorting.
     *
     * @param  array{site_id?: int, parent_id?: int|null, search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedZones(array $filters = []): LengthAwarePaginator
    {
        return Zone::query()
            ->with(['site', 'parent'])
            ->withCount(['children', 'materials'])
            ->where('site_id', auth()->user()->current_site_id)
            /*->when($filters['site_id'] ?? null, fn (Builder $query, int $siteId) => $query->where('site_id', $siteId))*/
            /*->when(
                array_key_exists('parent_id', $filters),
                fn (Builder $query) => $query->where('parent_id', $filters['parent_id'])
            )*/
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySorting($query, $sortBy, $filters['sort_direction'] ?? 'asc'),
                fn (Builder $query) => $query->orderBy('name')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get child zones for a parent zone.
     */
    public function getChildZones(Zone $zone): Collection
    {
        return $zone->children()
            ->withCount(['children', 'materials'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get available parent zones for a zone (excluding itself and its descendants).
     */
    public function getAvailableParentZones(int $siteId, ?Zone $excludeZone = null): Collection
    {
        return Zone::query()
            ->where('site_id', $siteId)
            ->where('allow_material', false) // Only zones that don't allow materials can be parents
            ->when($excludeZone, function (Builder $query) use ($excludeZone) {
                // Exclude the zone itself and all its descendants
                $descendantIds = $this->getAllDescendantIds($excludeZone);
                $descendantIds[] = $excludeZone->id;

                return $query->whereNotIn('id', $descendantIds);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all descendant IDs for a zone recursively.
     *
     * @return array<int>
     */
    public function getAllDescendantIds(Zone $zone): array
    {
        $ids = [];

        foreach ($zone->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllDescendantIds($child));
        }

        return $ids;
    }

    /**
     * Check if a zone can be deleted.
     */
    public function canDelete(Zone $zone): bool
    {
        // Cannot delete if zone has children or materials
        return $zone->children()->doesntExist() && $zone->materials()->doesntExist();
    }

    /**
     * Apply search filter to zones query.
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'ILIKE', "%{$search}%");
    }

    /**
     * Apply sorting to zones query.
     */
    private function applySorting(Builder $query, string $sortBy, string $direction): Builder
    {
        $allowedSorts = ['name', 'allow_material', 'children_count', 'materials_count', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $direction === 'desc' ? 'desc' : 'asc');
        }

        return $query;
    }
}
