<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use XetaSuite\Models\Material;

class MaterialObserver
{
    /**
     * Handle the "deleting" event.
     *
     * Save the material name in related records before the FK is set to null.
     */
    public function deleting(Material $material): void
    {
        $name = $material->name;

        $material->cleanings()
            ->update(['material_name' => $name]);

        $material->incidents()
            ->update(['material_name' => $name]);

        $material->maintenances()
            ->update(['material_name' => $name]);

        $material->items()->detach();
    }
}
