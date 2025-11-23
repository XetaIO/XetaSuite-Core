<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use Illuminate\Support\Facades\Auth;
use XetaSuite\Models\Item;

class ItemObserver
{
    /**
     * Handle the "creating" event.
     */
    public function creating(Item $item): void
    {
        $item->user_id = Auth::id();
        $item->site_id = getPermissionsTeamId();
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(Item $item): void
    {
        $item->materials()->detach();
    }
}
