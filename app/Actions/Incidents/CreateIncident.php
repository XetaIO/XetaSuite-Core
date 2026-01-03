<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Incidents;

use Illuminate\Support\Facades\DB;
use XetaSuite\Enums\Incidents\IncidentSeverity;
use XetaSuite\Enums\Incidents\IncidentStatus;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Material;
use XetaSuite\Models\User;

class CreateIncident
{
    /**
     * Create a new incident.
     *
     * @param  User  $user  The user creating the incident.
     * @param  array  $data  The data for the new incident.
     */
    public function handle(User $user, array $data): Incident
    {
        return DB::transaction(function () use ($user, $data) {
            $material = Material::findOrFail($data['material_id']);

            $incident = Incident::create([
                'site_id' => $material->site_id,
                'material_id' => $material->id,
                'maintenance_id' => $data['maintenance_id'] ?? null,
                'reported_by_id' => $user->id,
                'reported_by_name' => $user->full_name,
                'description' => $data['description'],
                'started_at' => $data['started_at'] ?? now(),
                'resolved_at' => $data['resolved_at'] ?? null,
                'status' => isset($data['resolved_at']) ? IncidentStatus::RESOLVED : IncidentStatus::OPEN,
                'severity' => $data['severity'] ?? IncidentSeverity::LOW,
            ]);

            return $incident->load(['material', 'maintenance', 'reporter']);
        });
    }
}
