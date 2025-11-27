<?php

declare(strict_types=1);

namespace XetaSuite\Observers;

use XetaSuite\Models\Item;

class ItemObserver
{
    /**
     * Handle the "deleting" event.
     *
     * Detach related records before deletion.
     * Note: item_movements, item_prices, item_material, and item_user
     * are already handled by cascadeOnDelete in migrations.
     */
    public function deleting(Item $item): bool
    {
        // Prevent deletion if item has movements (stock history protection)
        if ($item->movements()->exists()) {
            return false;
        }

        // Detach materials and recipients (handled by cascade but explicit for clarity)
        $item->materials()->detach();
        $item->recipients()->detach();

        return true;
    }
}
