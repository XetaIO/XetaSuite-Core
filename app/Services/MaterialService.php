<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use XetaSuite\Models\Material;
use XetaSuite\Models\Zone;

class MaterialService
{
    /**
     * Get a paginated list of materials with optional search and sorting.
     *
     * @param  array{zone_id?: int, search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedMaterials(array $filters = []): LengthAwarePaginator
    {
        $currentSiteId = auth()->user()->current_site_id;

        return Material::query()
            ->with(['zone', 'creator'])
            ->whereHas('zone', fn (Builder $query) => $query->where('site_id', $currentSiteId))
            ->when($filters['zone_id'] ?? null, fn (Builder $query, int $zoneId) => $query->where('zone_id', $zoneId))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySorting($query, $sortBy, $filters['sort_direction'] ?? 'asc'),
                fn (Builder $query) => $query->orderBy('name')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get materials for a specific zone.
     */
    public function getMaterialsForZone(Zone $zone): Collection
    {
        return $zone->materials()
            ->with(['creator'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get available zones for a material (zones that allow materials on current site).
     */
    public function getAvailableZones(): Collection
    {
        $currentSiteId = auth()->user()->current_site_id;

        return Zone::query()
            ->where('site_id', $currentSiteId)
            ->where('allow_material', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get available recipients for cleaning alerts (users with access to current site).
     */
    public function getAvailableRecipients(): Collection
    {
        $currentSiteId = auth()->user()->current_site_id;

        return \XetaSuite\Models\User::query()
            ->whereHas('sites', fn (Builder $query) => $query->where('site_id', $currentSiteId))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }

    /**
     * Apply search filter to materials query.
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search) {
            $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('description', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Apply sorting to materials query.
     */
    private function applySorting(Builder $query, string $sortBy, string $direction): Builder
    {
        $allowedColumns = ['name', 'created_at', 'last_cleaning_at'];
        $sortBy = in_array($sortBy, $allowedColumns) ? $sortBy : 'name';
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sortBy, $direction);
    }
}
