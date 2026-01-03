<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Materials;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Material;

class DeleteMaterial
{
    /**
     * Delete a material record.
     *
     * @param  Material  $material  The material to delete.
     */
    public function handle(Material $material): bool
    {
        return DB::transaction(function () use ($material) {
            return $material->delete();
        });
    }
}
