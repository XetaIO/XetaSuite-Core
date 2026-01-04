<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use XetaSuite\Enums\Incidents\IncidentSeverity;
use XetaSuite\Enums\Incidents\IncidentStatus;
use XetaSuite\Enums\Maintenances\MaintenanceStatus;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Site;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     * On HQ: aggregated stats from all sites.
     * On regular site: stats for current site only.
     */
    public function stats(): JsonResponse
    {
        $user = auth()->user();
        $isHq = isOnHeadquarters();

        // Build base queries depending on HQ or regular site
        $siteFilter = function ($query) use ($isHq, $user): void {
            if (! $isHq) {
                $query->where('site_id', $user->current_site_id);
            }
        };

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Maintenances this month
        $maintenancesThisMonth = Maintenance::query()
            ->tap($siteFilter)
            ->whereBetween('started_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Compare with last month for trend
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        $maintenancesLastMonth = Maintenance::query()
            ->tap($siteFilter)
            ->whereBetween('started_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $maintenancesTrend = $maintenancesLastMonth > 0
            ? round((($maintenancesThisMonth - $maintenancesLastMonth) / $maintenancesLastMonth) * 100)
            : ($maintenancesThisMonth > 0 ? 100 : 0);

        // Open incidents (not resolved or closed)
        $openIncidents = Incident::query()
            ->tap($siteFilter)
            ->whereIn('status', [IncidentStatus::OPEN->value, IncidentStatus::IN_PROGRESS->value])
            ->count();

        // Compare with last week for trend
        $openIncidentsLastWeek = Incident::query()
            ->tap($siteFilter)
            ->whereIn('status', [IncidentStatus::OPEN->value, IncidentStatus::IN_PROGRESS->value])
            ->where('created_at', '<=', Carbon::now()->subWeek())
            ->count();

        $incidentsTrend = $openIncidentsLastWeek > 0
            ? round((($openIncidents - $openIncidentsLastWeek) / $openIncidentsLastWeek) * 100)
            : ($openIncidents > 0 ? 100 : 0);

        // Total items in stock (current_stock = item_entry_total - item_exit_total)
        $itemsInStock = (int) Item::query()
            ->tap($siteFilter)
            ->selectRaw('SUM(item_entry_total - item_exit_total) as total')
            ->value('total') ?? 0;

        // Cleanings this month
        $cleaningsThisMonth = Cleaning::query()
            ->tap($siteFilter)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $cleaningsLastMonth = Cleaning::query()
            ->tap($siteFilter)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $cleaningsTrend = $cleaningsLastMonth > 0
            ? round((($cleaningsThisMonth - $cleaningsLastMonth) / $cleaningsLastMonth) * 100)
            : ($cleaningsThisMonth > 0 ? 100 : 0);

        // Incidents summary
        $incidentsByStatus = Incident::query()
            ->tap($siteFilter)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $incidentsBySeverity = Incident::query()
            ->tap($siteFilter)
            ->select('severity', DB::raw('count(*) as count'))
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        $incidentsSummary = [
            'total' => array_sum($incidentsByStatus),
            'open' => $incidentsByStatus[IncidentStatus::OPEN->value] ?? 0,
            'in_progress' => $incidentsByStatus[IncidentStatus::IN_PROGRESS->value] ?? 0,
            'resolved' => ($incidentsByStatus[IncidentStatus::RESOLVED->value] ?? 0)
                        + ($incidentsByStatus[IncidentStatus::CLOSED->value] ?? 0),
            'by_severity' => [
                'critical' => $incidentsBySeverity[IncidentSeverity::CRITICAL->value] ?? 0,
                'high' => $incidentsBySeverity[IncidentSeverity::HIGH->value] ?? 0,
                'medium' => $incidentsBySeverity[IncidentSeverity::MEDIUM->value] ?? 0,
                'low' => $incidentsBySeverity[IncidentSeverity::LOW->value] ?? 0,
            ],
        ];

        // Low stock items - prioritize:
        // 1. Items with warning enabled and below threshold
        // 2. Items with critical stock (below critical threshold)
        // 3. Items with lowest stock overall
        $lowStockItems = Item::query()
            ->tap($siteFilter)
            ->selectRaw('*, (item_entry_total - item_exit_total) as calculated_stock')
            ->where(function ($query): void {
                // Items below critical threshold (if enabled)
                $query->where(function ($q): void {
                    $q->where('number_critical_enabled', true)
                    ->whereRaw('(item_entry_total - item_exit_total) < number_critical_minimum');
                })
                // OR Items below warning threshold (if enabled)
                ->orWhere(function ($q): void {
                    $q->where('number_warning_enabled', true)
                      ->whereRaw('(item_entry_total - item_exit_total) < number_warning_minimum');
                })
                // OR items with very low stock (less than 10 units)
                ->orWhereRaw('(item_entry_total - item_exit_total) < 10 AND (item_entry_total - item_exit_total) >= 0');
            })
            // Order by priority: 1. Critical, 2. Warning, 3. Low stock, then by stock level
            ->orderByRaw("
                CASE
                    WHEN number_critical_enabled = true AND (item_entry_total - item_exit_total) < number_critical_minimum THEN 1
                    WHEN number_warning_enabled = true AND (item_entry_total - item_exit_total) < number_warning_minimum THEN 2
                    ELSE 3
                END ASC,
                (item_entry_total - item_exit_total) ASC
            ")
            ->limit(10)
            ->get(['id', 'name', 'reference', 'item_entry_total', 'item_exit_total', 'number_warning_minimum', 'number_warning_enabled', 'number_critical_minimum', 'number_critical_enabled'])
            ->map(fn (Item $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'reference' => $item->reference,
                'current_stock' => $item->current_stock,
                'min_stock' => $item->number_warning_enabled ? $item->number_warning_minimum : 10,
                'stock_status' => $item->stock_status,
                'stock_status_color' => $item->stock_status_color,
            ]);

        // Upcoming maintenances (planned)
        // First try to get future planned maintenances
        $upcomingMaintenances = Maintenance::query()
            ->with('material:id,name')
            ->tap($siteFilter)
            ->where('status', MaintenanceStatus::PLANNED->value)
            ->where('started_at', '>', Carbon::now())
            ->orderBy('started_at')
            ->limit(5)
            ->get(['id', 'material_id', 'material_name', 'description', 'type', 'started_at']);

        // If no future maintenances, get the most recent planned ones
        if ($upcomingMaintenances->isEmpty()) {
            $upcomingMaintenances = Maintenance::query()
                ->with('material:id,name')
                ->tap($siteFilter)
                ->where('status', MaintenanceStatus::PLANNED->value)
                ->orderBy('started_at', 'desc')
                ->limit(5)
                ->get(['id', 'material_id', 'material_name', 'description', 'type', 'started_at']);
        }

        $upcomingMaintenances = $upcomingMaintenances->map(fn (Maintenance $m) => [
            'id' => $m->id,
            'title' => $m->description,
            'location' => $m->material?->name ?? $m->material_name ?? '-',
            'date' => $m->started_at?->format('d M Y - H:i'),
            'priority' => 'medium',
            'type' => $m->type?->value ?? 'preventive',
        ]);

        // Recent activities (last events: maintenances, incidents, cleanings, item movements)
        $recentMaintenances = Maintenance::query()
            ->with('material:id,name')
            ->tap($siteFilter)
            ->latest('updated_at')
            ->limit(5)
            ->get(['id', 'description', 'material_id', 'status', 'updated_at'])
            ->map(fn (Maintenance $m) => [
                'id' => 'm_' . $m->id,
                'type' => 'maintenance',
                'title' => $m->description,
                'description' => $m->material?->name ?? '',
                'time' => $m->updated_at,
                'status' => $m->status?->value === 'completed' ? 'completed' : 'in_progress',
            ]);

        $recentIncidents = Incident::query()
            ->with('material:id,name')
            ->tap($siteFilter)
            ->latest('updated_at')
            ->limit(5)
            ->get(['id', 'description', 'material_id', 'status', 'updated_at'])
            ->map(fn (Incident $i) => [
                'id' => 'i_' . $i->id,
                'type' => 'incident',
                'title' => $i->description,
                'description' => $i->material?->name ?? '',
                'time' => $i->updated_at,
                'status' => match ($i->status?->value) {
                    'resolved', 'closed' => 'completed',
                    'in_progress' => 'in_progress',
                    default => 'pending',
                },
            ]);

        $recentCleanings = Cleaning::query()
            ->with('material:id,name')
            ->tap($siteFilter)
            ->latest('updated_at')
            ->limit(5)
            ->get(['id', 'description', 'material_id', 'type', 'updated_at'])
            ->map(fn (Cleaning $c) => [
                'id' => 'c_' . $c->id,
                'type' => 'cleaning',
                'title' => $c->description ?? __('Nettoyage'),
                'description' => $c->material?->name ?? '',
                'time' => $c->updated_at,
                'status' => 'completed',
            ]);

        $recentItemMovements = ItemMovement::query()
            ->with('item:id,name,site_id')
            ->whereHas('item', $siteFilter)
            ->latest('movement_date')
            ->limit(5)
            ->get(['id', 'item_id', 'type', 'quantity', 'movement_date', 'updated_at'])
            ->map(fn (ItemMovement $im) => [
                'id' => 'im_' . $im->item?->id,
                'type' => 'item_movement',
                'title' => $im->type === 'entry'
                    ? __(':qty entrée(s)', ['qty' => $im->quantity])
                    : __(':qty sortie(s)', ['qty' => $im->quantity]),
                'description' => $im->item?->name ?? '',
                'time' => $im->movement_date ?? $im->updated_at,
                'status' => $im->type,
            ]);

        $recentActivities = $recentMaintenances
            ->concat($recentIncidents)
            ->concat($recentCleanings)
            ->concat($recentItemMovements)
            ->sortByDesc('time')
            ->take(10)
            ->map(fn ($activity) => [
                ...$activity,
                'time' => Carbon::parse($activity['time'])->diffForHumans(),
            ])
            ->values();

        return response()->json([
            'stats' => [
                'maintenances_this_month' => $maintenancesThisMonth,
                'maintenances_trend' => $maintenancesTrend,
                'open_incidents' => $openIncidents,
                'incidents_trend' => $incidentsTrend,
                'items_in_stock' => (int) $itemsInStock,
                'cleanings_this_month' => $cleaningsThisMonth,
                'cleanings_trend' => $cleaningsTrend,
            ],
            'incidents_summary' => $incidentsSummary,
            'low_stock_items' => $lowStockItems,
            'upcoming_maintenances' => $upcomingMaintenances,
            'recent_activities' => $recentActivities,
            'is_headquarters' => $isHq,
        ]);
    }

    /**
     * Get chart data for maintenances evolution (last 6 months).
     */
    public function chartsData(): JsonResponse
    {
        $user = auth()->user();
        $isHq = isOnHeadquarters();

        $siteFilter = function ($query) use ($isHq, $user): void {
            if (! $isHq) {
                $query->where('site_id', $user->current_site_id);
            }
        };

        // Maintenances by month (last 6 months)
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->format('Y-m'));
        }

        $maintenancesByMonth = Maintenance::query()
            ->tap($siteFilter)
            ->where('started_at', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->select(
                DB::raw("TO_CHAR(started_at, 'YYYY-MM') as month"),
                DB::raw('count(*) as count')
            )
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $maintenancesEvolution = $months->map(fn ($month) => [
            'month' => Carbon::parse($month . '-01')->format('M Y'),
            'count' => $maintenancesByMonth[$month] ?? 0,
        ]);

        // Incidents by month (last 6 months)
        $incidentsByMonth = Incident::query()
            ->tap($siteFilter)
            ->where('started_at', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->select(
                DB::raw("TO_CHAR(started_at, 'YYYY-MM') as month"),
                DB::raw('count(*) as count')
            )
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $incidentsEvolution = $months->map(fn ($month) => [
            'month' => Carbon::parse($month . '-01')->format('M Y'),
            'count' => $incidentsByMonth[$month] ?? 0,
        ]);

        return response()->json([
            'maintenances_evolution' => $maintenancesEvolution,
            'incidents_evolution' => $incidentsEvolution,
        ]);
    }
}
