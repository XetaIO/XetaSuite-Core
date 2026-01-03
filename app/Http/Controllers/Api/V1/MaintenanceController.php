<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Maintenances\CreateMaintenance;
use XetaSuite\Actions\Maintenances\DeleteMaintenance;
use XetaSuite\Actions\Maintenances\UpdateMaintenance;
use XetaSuite\Http\Requests\V1\Maintenances\StoreMaintenanceRequest;
use XetaSuite\Http\Requests\V1\Maintenances\UpdateMaintenanceRequest;
use XetaSuite\Http\Resources\V1\Incidents\IncidentResource;
use XetaSuite\Http\Resources\V1\Maintenances\MaintenanceDetailResource;
use XetaSuite\Http\Resources\V1\Maintenances\MaintenanceResource;
use XetaSuite\Models\Maintenance;
use XetaSuite\Services\MaintenanceService;

class MaintenanceController extends Controller
{
    public function __construct(
        private readonly MaintenanceService $maintenanceService
    ) {
    }

    /**
     * Display a listing of maintenances.
     * Only shows maintenances for the user's current site.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Maintenance::class);

        $maintenances = $this->maintenanceService->getPaginatedMaintenances([
            'material_id' => request('material_id'),
            'status' => request('status'),
            'type' => request('type'),
            'realization' => request('realization'),
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return MaintenanceResource::collection($maintenances);
    }

    /**
     * Store a newly created maintenance.
     */
    public function store(StoreMaintenanceRequest $request, CreateMaintenance $action): MaintenanceDetailResource
    {
        $maintenance = $action->handle($request->user(), $request->validated());

        return new MaintenanceDetailResource($maintenance);
    }

    /**
     * Display the specified maintenance.
     */
    public function show(Maintenance $maintenance): MaintenanceDetailResource
    {
        $this->authorize('view', $maintenance);

        $maintenance->load([
            'material.zone',
            'incidents',
            'operators',
            'companies',
            'creator',
            'editor',
            'site',
            'itemMovements.item',
        ]);

        return new MaintenanceDetailResource($maintenance);
    }

    /**
     * Update the specified maintenance.
     */
    public function update(UpdateMaintenanceRequest $request, Maintenance $maintenance, UpdateMaintenance $action): MaintenanceDetailResource
    {
        $maintenance = $action->handle($maintenance, $request->user(), $request->validated());

        return new MaintenanceDetailResource($maintenance);
    }

    /**
     * Delete the specified maintenance.
     */
    public function destroy(Maintenance $maintenance, DeleteMaintenance $action): JsonResponse
    {
        $this->authorize('delete', $maintenance);

        $action->handle($maintenance);

        return response()->json(null, 204);
    }

    /**
     * Get paginated incidents for a maintenance.
     */
    public function incidents(Maintenance $maintenance): AnonymousResourceCollection
    {
        $this->authorize('view', $maintenance);

        $incidents = $this->maintenanceService->getMaintenanceIncidents(
            $maintenance,
            (int) request('per_page', 10)
        );

        return IncidentResource::collection($incidents);
    }

    /**
     * Get paginated item movements for a maintenance.
     */
    public function itemMovements(Maintenance $maintenance): JsonResponse
    {
        $this->authorize('view', $maintenance);

        $movements = $this->maintenanceService->getMaintenanceItemMovements(
            $maintenance,
            (int) request('per_page', 10)
        );

        $totalCost = $this->maintenanceService->getMaintenanceTotalCost($maintenance);

        return response()->json([
            'data' => $movements->getCollection()->map(fn ($mov) => [
                'id' => $mov->id,
                'item_id' => $mov->item_id,
                'item_name' => $mov->item?->name,
                'item_reference' => $mov->item?->reference,
                'quantity' => $mov->quantity,
                'unit_price' => (float) $mov->unit_price,
                'total_price' => (float) $mov->total_price,
                'created_by_name' => $mov->creator?->full_name ?? $mov->created_by_name,
                'created_at' => $mov->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
                'total_cost' => (float) $totalCost,
            ],
        ]);
    }

    /**
     * Get available materials for maintenance creation.
     */
    public function availableMaterials(): JsonResponse
    {
        $this->authorize('viewAny', Maintenance::class);

        $materials = $this->maintenanceService->getAvailableMaterials(
            request('search')
        );

        return response()->json([
            'data' => $materials->map(fn ($material) => [
                'id' => $material->id,
                'name' => $material->name,
            ]),
        ]);
    }

    /**
     * Get available incidents for maintenance.
     */
    public function availableIncidents(): JsonResponse
    {
        $this->authorize('viewAny', Maintenance::class);

        $maintenanceId = request('maintenance_id') ? (int) request('maintenance_id') : null;
        $incidents = $this->maintenanceService->getAvailableIncidents(
            $maintenanceId,
            request('search')
        );

        return response()->json([
            'data' => $incidents->map(fn ($incident) => [
                'id' => $incident->id,
                'description' => $incident->description,
                'severity' => $incident->severity->value,
                'severity_label' => $incident->severity->label(),
                'status' => $incident->status->value,
                'status_label' => $incident->status->label(),
            ]),
        ]);
    }

    /**
     * Get available operators for maintenance.
     */
    public function availableOperators(): JsonResponse
    {
        $this->authorize('viewAny', Maintenance::class);

        $operators = $this->maintenanceService->getAvailableOperators(
            request('search')
        );

        return response()->json([
            'data' => $operators->map(fn ($user) => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
            ]),
        ]);
    }

    /**
     * Get available companies for maintenance.
     */
    public function availableCompanies(): JsonResponse
    {
        $this->authorize('viewAny', Maintenance::class);

        $companies = $this->maintenanceService->getAvailableCompanies(
            request('search')
        );

        return response()->json([
            'data' => $companies->map(fn ($company) => [
                'id' => $company->id,
                'name' => $company->name,
            ]),
        ]);
    }

    /**
     * Get available items for spare parts.
     */
    public function availableItems(): JsonResponse
    {
        $this->authorize('viewAny', Maintenance::class);

        $items = $this->maintenanceService->getAvailableItems(
            request('search')
        );

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Get type options.
     */
    public function typeOptions(): JsonResponse
    {
        return response()->json([
            'data' => $this->maintenanceService->getTypeOptions(),
        ]);
    }

    /**
     * Get status options.
     */
    public function statusOptions(): JsonResponse
    {
        return response()->json([
            'data' => $this->maintenanceService->getStatusOptions(),
        ]);
    }

    /**
     * Get realization options.
     */
    public function realizationOptions(): JsonResponse
    {
        return response()->json([
            'data' => $this->maintenanceService->getRealizationOptions(),
        ]);
    }
}
