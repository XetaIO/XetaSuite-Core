<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use Illuminate\Support\Facades\Auth;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\ItemMovement;

class MaintenanceObserver
{
    /**
     * Handle the "creating" event.
     */
    public function creating(Maintenance $maintenance): void
    {
        $maintenance->user_id = Auth::id();
        $maintenance->site_id = getPermissionsTeamId();
    }

    /**
     * Handle the "updating" event.
     */
    public function updating(Maintenance $maintenance): void
    {
        $maintenance->edited_by_id = Auth::id();
    }

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
                'movable_id'   => null,
            ]);
    }
}
