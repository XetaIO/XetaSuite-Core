<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Maintenances;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Maintenance;

class DeleteMaintenance
{
    /**
     * Delete a maintenance.
     *
     * @param  Maintenance  $maintenance  The maintenance to delete.
     */
    public function handle(Maintenance $maintenance): void
    {
        DB::transaction(function () use ($maintenance) {
            // Unlink incidents before deletion
            $maintenance->incidents()->update(['maintenance_id' => null]);

            // Delete related item movements
            $maintenance->itemMovements()->delete();

            // Delete the maintenance
            $maintenance->delete();
        });
    }
}
