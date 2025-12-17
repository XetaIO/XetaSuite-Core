<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use XetaSuite\Enums\Cleanings\CleaningType;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Material;

class CleaningService
{
    /**
     * Get a paginated list of cleanings with optional search and sorting.
     *
     * @param  array{material_id?: int, type?: string, search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedCleanings(array $filters = []): LengthAwarePaginator
    {
        $query = Cleaning::query()
            ->with(['site', 'material', 'creator']);

        // If not HQ, filter by current site
        if (!isOnHeadquarters()) {
            $query->where('site_id', auth()->user()->current_site_id);
        }

        return $query
            ->when($filters['material_id'] ?? null, fn (Builder $query, int $materialId) => $query->where('material_id', $materialId))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySorting($query, $sortBy, $filters['sort_direction'] ?? 'desc'),
                fn (Builder $query) => $query->orderByDesc('created_at')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get available materials for the current site.
     */
    public function getAvailableMaterials(): Collection
    {
        $currentSiteId = auth()->user()->current_site_id;

        return Material::query()
            ->select(['id', 'name'])
            ->where('site_id', $currentSiteId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get available cleaning types.
     *
     * @return array<array{value: string, label: string}>
     */
    public function getTypeOptions(): array
    {
        return collect(CleaningType::cases())->map(fn (CleaningType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ])->values()->toArray();
    }

    /**
     * Apply search filter to query.
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('description', 'ilike', "%{$search}%")
                ->orWhere('material_name', 'ilike', "%{$search}%");
        });
    }

    /**
     * Apply sorting to query.
     */
    private function applySorting(Builder $query, string $sortBy, string $direction): Builder
    {
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        return match ($sortBy) {
            'type' => $query->orderBy('type', $direction),
            'material_name' => $query->orderBy('material_name', $direction),
            'created_at' => $query->orderBy('created_at', $direction),
            default => $query->orderByDesc('created_at'),
        };
    }
}
