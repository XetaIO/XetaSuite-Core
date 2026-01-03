<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use XetaSuite\Models\Zone;
use XetaSuite\Services\Concerns\HasSearchAndSort;

class ZoneService
{
    use HasSearchAndSort;

    private const SEARCH_COLUMNS = ['name'];

    private const ALLOWED_SORTS = ['name', 'allow_material', 'children_count', 'material_count', 'created_at'];

    /**
     * Get a paginated list of zones with optional search and sorting.
     *
     * @param  array{site_id?: int, parent_id?: int|null, search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedZones(array $filters = []): LengthAwarePaginator
    {
        $query = Zone::query()
            ->with(['site', 'parent'])
            ->withCount(['children', 'materials'])
            ->forCurrentSite()
            ->when(
                $filters['search'] ?? null,
                fn (Builder $q, string $search) => $this->applySearchFilter($q, $search, self::SEARCH_COLUMNS)
            )
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $q, string $sortBy) => $this->applySortFilter($q, $sortBy, $filters['sort_direction'] ?? 'asc', self::ALLOWED_SORTS),
                fn (Builder $q) => $q->orderBy('name')
            );

        return $query->paginate($filters['per_page'] ?? 20);
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

        $zone->load('children');

        foreach ($zone->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllDescendantIds($child));
        }

        return $ids;
    }

    /**
     * Get hierarchical tree of zones for a site.
     * Returns only root zones (parent_id = null) with nested children loaded recursively.
     * Also includes materials for zones that allow them.
     */
    public function getZoneTree(int $siteId): Collection
    {
        return Zone::query()
            ->where('site_id', $siteId)
            ->whereNull('parent_id') // Only root zones
            ->with(['childrenRecursive', 'materials:id,zone_id,name,description'])
            ->withCount(['children', 'materials'])
            ->orderBy('name')
            ->get();
    }
}
