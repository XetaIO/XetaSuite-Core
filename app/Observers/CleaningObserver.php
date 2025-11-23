<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use Illuminate\Support\Facades\Auth;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Material;

class CleaningObserver
{
    /**
     * Handle the "creating" event.
     */
    public function creating(Cleaning $cleaning): void
    {
        $cleaning->site_id = getPermissionsTeamId();
    }

    /**
     * Handle the "created" event.
     */
    public function created(Cleaning $cleaning): void
    {
        $cleaning->material()->update(['last_cleaning_at' => now()]);
    }
}
