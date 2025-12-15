<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Company;
use XetaSuite\Models\Maintenance;

class CompanyService
{
    /**
     * Get a paginated list of companies with optional search and sorting.
     *
     * @param  array{search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedCompanies(array $filters = []): LengthAwarePaginator
    {
        return Company::query()
            ->with('creator')
            ->withCount('maintenances')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applySorting($query, $sortBy, $filters['sort_direction'] ?? 'asc'),
                fn (Builder $query) => $query->orderBy('name')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Apply search filter to companies query.
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('description', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Apply sorting to companies query.
     */
    private function applySorting(Builder $query, string $sortBy, string $direction): Builder
    {
        $allowedSorts = ['name', 'maintenances_count', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $direction === 'desc' ? 'desc' : 'asc');
        }

        return $query;
    }

    /**
     * Get paginated maintenances for a company with optional search and sorting.
     *
     * @param  array{search?: string, sort_by?: string, sort_direction?: string, per_page?: int}  $filters
     */
    public function getPaginatedMaintenances(Company $company, array $filters = []): LengthAwarePaginator
    {
        return $company->maintenances()
            ->with(['material', 'site', 'creator'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applyMaintenanceSearch($query, $search))
            ->when(
                $filters['sort_by'] ?? null,
                fn (Builder $query, string $sortBy) => $this->applyMaintenanceSorting($query, $sortBy, $filters['sort_direction'] ?? 'asc'),
                fn (Builder $query) => $query->orderByDesc('created_at')
            )
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Apply search filter to maintenances query.
     */
    private function applyMaintenanceSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('description', 'ILIKE', "%{$search}%")
                ->orWhere('reason', 'ILIKE', "%{$search}%")
                ->orWhereHas('material', fn (Builder $m) => $m->where('name', 'ILIKE', "%{$search}%"))
                ->orWhereHas('site', fn (Builder $s) => $s->where('name', 'ILIKE', "%{$search}%"));
        });
    }

    /**
     * Apply sorting to maintenances query.
     */
    private function applyMaintenanceSorting(Builder $query, string $sortBy, string $direction): Builder
    {
        $allowedSorts = ['type', 'status', 'started_at', 'resolved_at', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $direction === 'desc' ? 'desc' : 'asc');
        }

        return $query;
    }

    /**
     * Get statistics for a company.
     *
     * @return array<string, mixed>
     */
    public function getCompanyStats(Company $company): array
    {
        $maintenanceIds = $company->maintenances()->pluck('maintenances.id');

        // Maintenances by site
        $maintenancesBySite = Maintenance::query()
            ->whereIn('id', $maintenanceIds)
            ->select('site_id', DB::raw('count(*) as count'))
            ->with('site:id,name')
            ->groupBy('site_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'site_id' => $item->site_id,
                'site_name' => $item->site?->name ?? 'Unknown',
                'count' => $item->count,
            ]);

        // Maintenances by type
        $maintenancesByType = Maintenance::query()
            ->whereIn('id', $maintenanceIds)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => [
                'type' => $item->type->value,
                'type_label' => $item->type->label(),
                'count' => $item->count,
            ]);

        // Maintenances by status
        $maintenancesByStatus = Maintenance::query()
            ->whereIn('id', $maintenanceIds)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => [
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'count' => $item->count,
            ]);

        // Maintenances by realization
        $maintenancesByRealization = Maintenance::query()
            ->whereIn('id', $maintenanceIds)
            ->select('realization', DB::raw('count(*) as count'))
            ->groupBy('realization')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => [
                'realization' => $item->realization->value,
                'realization_label' => $item->realization->label(),
                'count' => $item->count,
            ]);

        // Maintenances by month (last 12 months)
        $maintenancesByMonth = Maintenance::query()
            ->whereIn('id', $maintenanceIds)
            ->where('started_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw("to_char(started_at, 'YYYY-MM') as month"),
                DB::raw('count(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($item) => [
                'month' => $item->month,
                'count' => $item->count,
            ]);

        return [
            'total_maintenances' => $maintenanceIds->count(),
            'maintenances_by_site' => $maintenancesBySite,
            'maintenances_by_type' => $maintenancesByType,
            'maintenances_by_status' => $maintenancesByStatus,
            'maintenances_by_realization' => $maintenancesByRealization,
            'maintenances_by_month' => $maintenancesByMonth,
        ];
    }
}
