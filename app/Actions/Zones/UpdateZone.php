<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Zones;

use XetaSuite\Models\Zone;

class UpdateZone
{
    /**
     * Update an existing zone.
     *
     * @param  array{name?: string, parent_id?: int|null, allow_material?: bool}  $data
     */
    public function handle(Zone $zone, array $data): Zone
    {
        $zone->update($data);

        return $zone->fresh();
    }
}
