<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use XetaSuite\Enums\Cleanings\CleaningType;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Material;
use XetaSuite\Services\Concerns\HasSearchAndSort;

class CleaningService
{
    use HasSearchAndSort;

    /**
     * Search columns for cleanings.
     *
     * @var array<int, string>
     */
    private const SEARCH_COLUMNS = ['description', 'material_name'];

    /**
     * Allowed sort fields for cleanings.
     *
     * @var array<int, string>
     */
    private const ALLOWED_SORTS = ['type', 'material_name', 'created_at'];

    /**
     * Get a paginated list of cleanings with optional search and sorting.
     *
     * @param  array{material_id?: int, type?: string, search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedCleanings(array $filters = []): LengthAwarePaginator
    {
        return Cleaning::query()
            ->with(['site', 'material', 'creator'])
            ->forCurrentSite()
            ->when($filters['material_id'] ?? null, fn (Builder $query, int $materialId) => $query->where('material_id', $materialId))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearchFilter($query, $search, self::SEARCH_COLUMNS))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySortFilter($query, $sortBy, $filters['sort_direction'] ?? 'desc', self::ALLOWED_SORTS, 'created_at', 'desc'),
                fn (Builder $query) => $query->orderByDesc('created_at')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get available materials for the current site.
     */
    public function getAvailableMaterials(): Collection
    {
        return Material::query()
            ->select(['id', 'name'])
            ->forCurrentSite()
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
}
