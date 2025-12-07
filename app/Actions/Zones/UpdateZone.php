<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Zones;

use XetaSuite\Models\Zone;

class UpdateZone
{
    /**
     * Update a zone.
     *
     * @param  Zone  $zone  The zone to update.
     * @param  array  $data  The data to update the zone with.
     */
    public function handle(Zone $zone, array $data): Zone
    {
        $zone->update($data);

        return $zone->fresh();
    }
}
