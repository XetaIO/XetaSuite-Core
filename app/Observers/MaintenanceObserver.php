<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use XetaSuite\Models\Maintenance;

class MaintenanceObserver
{
    /**
     * Handle the "deleting" event.
     */
    public function deleting(Maintenance $maintenance): void
    {
        $maintenance->companies()->detach();

        $maintenance->operators()->detach();

        $maintenance->incidents()
            ->update(['maintenance_id' => null]);

        $maintenance->itemMovements()
            ->update([
                'movable_type' => null,
                'movable_id' => null,
            ]);
    }
}
