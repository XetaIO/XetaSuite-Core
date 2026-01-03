<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Materials;

use Illuminate\Support\Facades\DB;
use XetaSuite\Enums\Materials\CleaningFrequency;
use XetaSuite\Models\Material;

class UpdateMaterial
{
    /**
     * Update a material record.
     *
     * @param  Material  $material  The material to update.
     * @param  array  $data  The data to update the material with.
     */
    public function handle(Material $material, array $data): Material
    {
        return DB::transaction(function () use ($material, $data) {
            $updateData = [];

            if (isset($data['zone_id'])) {
                $updateData['zone_id'] = $data['zone_id'];
            }

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (array_key_exists('description', $data)) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['cleaning_alert'])) {
                $updateData['cleaning_alert'] = $data['cleaning_alert'];

                // If cleaning alert is disabled, reset related fields
                if (! $data['cleaning_alert']) {
                    $updateData['cleaning_alert_email'] = false;
                    $updateData['cleaning_alert_frequency_repeatedly'] = 0;
                    $updateData['cleaning_alert_frequency_type'] = CleaningFrequency::DAILY->value;
                    $material->recipients()->detach();
                }
            }

            if (isset($data['cleaning_alert_email'])) {
                $updateData['cleaning_alert_email'] = $data['cleaning_alert_email'];
            }

            if (isset($data['cleaning_alert_frequency_repeatedly'])) {
                $updateData['cleaning_alert_frequency_repeatedly'] = $data['cleaning_alert_frequency_repeatedly'];
            }

            if (isset($data['cleaning_alert_frequency_type'])) {
                $updateData['cleaning_alert_frequency_type'] = $data['cleaning_alert_frequency_type'];
            }

            $material->update($updateData);

            // Sync recipients if provided
            if (isset($data['recipients'])) {
                if ($material->cleaning_alert) {
                    $material->recipients()->sync($data['recipients']);
                } else {
                    $material->recipients()->detach();
                }
            }

            return $material->fresh(['zone', 'creator', 'recipients']);
        });
    }
}
