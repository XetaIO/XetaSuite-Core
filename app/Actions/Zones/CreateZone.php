<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Zones;

use XetaSuite\Models\Zone;

class CreateZone
{
    /**
     * Create a new zone.
     *
     * @param  array{site_id: int, name: string, parent_id?: int|null, allow_material?: bool}  $data
     */
    public function handle(array $data): Zone
    {
        return Zone::create([
            'site_id' => $data['site_id'],
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
            'allow_material' => $data['allow_material'] ?? false,
        ]);
    }
}
