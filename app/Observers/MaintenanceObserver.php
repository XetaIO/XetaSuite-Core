<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Maintenance;

class MaintenanceObserver
{
    /**
     * Handle the "deleted" event.
     */
    public function deleted(Maintenance $maintenance): void
    {
        $maintenance->companies()->detach();

        $maintenance->operators()->detach();

        $maintenance->incidents()
            ->update(['maintenance_id' => null]);

        ItemMovement::query()
            ->where('movable_type', Maintenance::class)
            ->where('movable_id', $maintenance->id)
            ->update([
                'movable_type' => null,
                'movable_id' => null,
            ]);
    }
}
