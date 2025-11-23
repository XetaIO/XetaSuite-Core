<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use Exception;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Material;

class MaterialObserver
{
    /**
     * Called before a delete.
     *
     * @param Material $material
     *
     * @throws Exception
     */
    public function deleting(Material $material): void
    {
         if ($material->cleanings()->exists()
             || $material->incidents()->exists()
             || $material->maintenances()->exists()) {
             throw new Exception('It is impossible to delete the material as long as it has linked records.');
        }
    }

    /**
     * Called after a delete.
     *
     * @param Material $material
     *
     * @return void
     */
    public function deleted(Material $material): void
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
