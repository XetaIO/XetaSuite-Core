<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use XetaSuite\Models\ItemMovement;

class ItemMovementObserver
{
    /**
     * Handle the "created" event.
     */
    public function created(ItemMovement $movement): void
    {
        if ($movement->type === 'entry') {
            $movement->item->increment('item_entry_total', $movement->quantity);
        } else {
            $movement->item->increment('item_exit_total', $movement->quantity);
        }
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(ItemMovement $movement): void
    {
        if ($movement->type === 'entry') {
            $movement->item->decrement('item_entry_total', $movement->quantity);
        } else {
            $movement->item->decrement('item_exit_total', $movement->quantity);
        }
    }
}
