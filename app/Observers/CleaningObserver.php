<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use XetaSuite\Models\Cleaning;

class CleaningObserver
{
    /**
     * Handle the "created" event.
     */
    public function created(Cleaning $cleaning): void
    {
        $cleaning->material()->update(['last_cleaning_at' => now()]);
    }
}
