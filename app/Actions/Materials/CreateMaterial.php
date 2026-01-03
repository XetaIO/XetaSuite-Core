<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Materials;

use Illuminate\Support\Facades\DB;
use XetaSuite\Enums\Materials\CleaningFrequency;
use XetaSuite\Models\Material;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

class CreateMaterial
{
    /**
     * Create a new material.
     *
     * @param  User  $user  The user creating the material.
     * @param  array  $data  The data for the new material.
     */
    public function handle(User $user, array $data): Material
    {
        return DB::transaction(function () use ($user, $data) {
            $zone = Zone::findOrFail($data['zone_id']);

            $material = Material::create([
                'site_id' => $zone->site_id,
                'zone_id' => $data['zone_id'],
                'created_by_id' => $user->id,
                'created_by_name' => $user->full_name,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'cleaning_alert' => $data['cleaning_alert'] ?? false,
                'cleaning_alert_email' => $data['cleaning_alert_email'] ?? false,
                'cleaning_alert_frequency_repeatedly' => $data['cleaning_alert_frequency_repeatedly'] ?? 0,
                'cleaning_alert_frequency_type' => $data['cleaning_alert_frequency_type'] ?? CleaningFrequency::DAILY->value,
            ]);

            // Sync recipients if provided and cleaning alert is enabled
            if (! empty($data['recipients']) && ($data['cleaning_alert'] ?? false)) {
                $material->recipients()->sync($data['recipients']);
            }

            return $material->load(['zone', 'creator', 'recipients']);
        });
    }
}
