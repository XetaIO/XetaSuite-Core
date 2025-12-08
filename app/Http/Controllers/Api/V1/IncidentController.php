<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Incidents\CreateIncident;
use XetaSuite\Actions\Incidents\DeleteIncident;
use XetaSuite\Actions\Incidents\UpdateIncident;
use XetaSuite\Http\Requests\V1\Incidents\StoreIncidentRequest;
use XetaSuite\Http\Requests\V1\Incidents\UpdateIncidentRequest;
use XetaSuite\Http\Resources\V1\Incidents\IncidentDetailResource;
use XetaSuite\Http\Resources\V1\Incidents\IncidentResource;
use XetaSuite\Models\Incident;
use XetaSuite\Services\IncidentService;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentService $incidentService
    ) {
    }

    /**
     * Display a listing of incidents.
     * Only shows incidents for the user's current site.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Incident::class);

        $incidents = $this->incidentService->getPaginatedIncidents([
            'material_id' => request('material_id'),
            'status' => request('status'),
            'severity' => request('severity'),
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return IncidentResource::collection($incidents);
    }

    /**
     * Store a newly created incident.
     */
    public function store(StoreIncidentRequest $request, CreateIncident $action): IncidentDetailResource
    {
        $incident = $action->handle($request->user(), $request->validated());

        return new IncidentDetailResource(
            $incident->load(['material', 'maintenance', 'reporter', 'site'])
        );
    }

    /**
     * Display the specified incident.
     */
    public function show(Incident $incident): IncidentDetailResource
    {
        $this->authorize('view', $incident);

        $incident->load(['material.zone', 'maintenance', 'reporter', 'editor', 'site']);

        return new IncidentDetailResource($incident);
    }

    /**
     * Update the specified incident.
     */
    public function update(UpdateIncidentRequest $request, Incident $incident, UpdateIncident $action): IncidentDetailResource
    {
        $incident = $action->handle($incident, $request->user(), $request->validated());

        return new IncidentDetailResource(
            $incident->load(['material.zone', 'maintenance', 'reporter', 'editor', 'site'])
        );
    }

    /**
     * Delete the specified incident.
     */
    public function destroy(Incident $incident, DeleteIncident $action): JsonResponse
    {
        $this->authorize('delete', $incident);

        $action->handle($incident);

        return response()->json(null, 204);
    }

    /**
     * Get available materials for incident creation.
     */
    public function availableMaterials(): JsonResponse
    {
        $this->authorize('viewAny', Incident::class);

        $materials = $this->incidentService->getAvailableMaterials();

        return response()->json([
            'data' => $materials->map(fn ($material) => [
                'id' => $material->id,
                'name' => $material->name,
            ]),
        ]);
    }

    /**
     * Get available maintenances for incident creation.
     */
    public function availableMaintenances(): JsonResponse
    {
        $this->authorize('viewAny', Incident::class);

        $materialId = request('material_id') ? (int) request('material_id') : null;
        $maintenances = $this->incidentService->getAvailableMaintenances($materialId);

        return response()->json([
            'data' => $maintenances->map(fn ($maintenance) => [
                'id' => $maintenance->id,
                'description' => $maintenance->description,
                'material_id' => $maintenance->material_id,
            ]),
        ]);
    }

    /**
     * Get severity options.
     */
    public function severityOptions(): JsonResponse
    {
        return response()->json([
            'data' => $this->incidentService->getSeverityOptions(),
        ]);
    }

    /**
     * Get status options.
     */
    public function statusOptions(): JsonResponse
    {
        return response()->json([
            'data' => $this->incidentService->getStatusOptions(),
        ]);
    }
}
