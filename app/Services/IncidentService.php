<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use XetaSuite\Enums\Incidents\IncidentSeverity;
use XetaSuite\Enums\Incidents\IncidentStatus;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Material;
use XetaSuite\Services\Concerns\HasSearchAndSort;

class IncidentService
{
    use HasSearchAndSort;

    private const array ALLOWED_SORTS = ['created_at', 'started_at', 'resolved_at', 'severity', 'status'];

    /**
     * Get a paginated list of incidents with optional search and sorting.
     *
     * @param  array{material_id?: int, status?: string, severity?: string, search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedIncidents(array $filters = []): LengthAwarePaginator
    {
        return Incident::query()
            ->with(['site', 'material', 'maintenance', 'reporter'])
            ->forCurrentSite()
            ->when($filters['material_id'] ?? null, fn (Builder $query, int $materialId) => $query->where('material_id', $materialId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['severity'] ?? null, fn (Builder $query, string $severity) => $query->where('severity', $severity))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySortFilter($query, $sortBy, $filters['sort_direction'] ?? 'desc', self::ALLOWED_SORTS, 'created_at', 'desc'),
                fn (Builder $query) => $query->orderByDesc('created_at')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get available materials for incident creation (materials on current site).
     */
    public function getAvailableMaterials(): Collection
    {
        return Material::query()
            ->forCurrentSite()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Get available maintenances for incident creation (maintenances on current site).
     */
    public function getAvailableMaintenances(?int $materialId = null, ?string $search = null): Collection
    {
        return Maintenance::query()
            ->with('material:id,name')
            ->forCurrentSite()
            ->when($materialId, fn (Builder $query, int $id) => $query->where('material_id', $id))
            ->when($search, fn (Builder $query, string $s) => $query->where(function (Builder $q) use ($s) {
                $q->where('id', 'like', "%{$s}%")
                    ->orWhere('description', 'ilike', "%{$s}%")
                    ->orWhereHas('material', fn (Builder $mq) => $mq->where('name', 'ilike', "%{$s}%"));
            }))
            ->orderByDesc('created_at')
            ->get(['id', 'description', 'material_id']);
    }

    /**
     * Get all severity options.
     *
     * @return array<array{value: string, label: string}>
     */
    public function getSeverityOptions(): array
    {
        return array_map(fn (IncidentSeverity $severity) => [
            'value' => $severity->value,
            'label' => $severity->label(),
        ], IncidentSeverity::cases());
    }

    /**
     * Get all status options.
     *
     * @return array<array{value: string, label: string}>
     */
    public function getStatusOptions(): array
    {
        return array_map(fn (IncidentStatus $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], IncidentStatus::cases());
    }

    /**
     * Apply search filter to query.
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        $searchTerm = '%' . mb_strtolower($search) . '%';

        return $query->where(function (Builder $q) use ($searchTerm) {
            $q->whereRaw('LOWER(description) LIKE ?', [$searchTerm])
                ->orWhereRaw('LOWER(material_name) LIKE ?', [$searchTerm])
                ->orWhereRaw('LOWER(reported_by_name) LIKE ?', [$searchTerm]);
        });
    }
}
