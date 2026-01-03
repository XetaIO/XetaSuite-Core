<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Incidents;

use Illuminate\Support\Facades\DB;
use XetaSuite\Enums\Incidents\IncidentStatus;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Material;
use XetaSuite\Models\User;

class UpdateIncident
{
    /**
     * Update an existing incident.
     *
     * @param  Incident  $incident  The incident to update.
     * @param  User  $user  The user updating the incident.
     * @param  array  $data  The data to update.
     */
    public function handle(Incident $incident, User $user, array $data): Incident
    {
        return DB::transaction(function () use ($incident, $user, $data) {
            $updateData = [
                'edited_by_id' => $user->id,
            ];

            if (isset($data['material_id'])) {
                $material = Material::findOrFail($data['material_id']);
                $updateData['material_id'] = $material->id;
                $updateData['site_id'] = $material->site_id;
            }

            if (array_key_exists('maintenance_id', $data)) {
                $updateData['maintenance_id'] = $data['maintenance_id'];
            }

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['severity'])) {
                $updateData['severity'] = $data['severity'];
            }

            if (isset($data['started_at'])) {
                $updateData['started_at'] = $data['started_at'];
            }

            if (array_key_exists('resolved_at', $data)) {
                $updateData['resolved_at'] = $data['resolved_at'];

                // Auto-update status based on resolved_at
                if ($data['resolved_at'] !== null && $incident->status === IncidentStatus::OPEN) {
                    $updateData['status'] = IncidentStatus::RESOLVED;
                } elseif ($data['resolved_at'] === null && $incident->status === IncidentStatus::RESOLVED) {
                    $updateData['status'] = IncidentStatus::OPEN;
                }
            }

            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            $incident->update($updateData);

            return $incident->fresh(['material', 'maintenance', 'reporter', 'editor']);
        });
    }
}
