<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Materials;

use XetaSuite\Models\Material;

class DeleteMaterial
{
    /**
     * Delete a material.
     *
     * The MaterialObserver handles:
     * - Preserving material_name in related cleanings, incidents, maintenances
     * - Detaching related items
     */
    public function handle(Material $material): bool
    {
        return $material->delete();
    }
}
