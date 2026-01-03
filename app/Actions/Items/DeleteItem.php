<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Items;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Item;

class DeleteItem
{
    /**
     * Delete an item if it has no associated movements.
     *
     * @param  Item  $item  The item to be deleted.
     * @return array{message: array|string|null, success: bool}
     */
    public function handle(Item $item): array
    {
        // Check if item has movements
        if ($item->movements()->count() > 0) {
            return [
                'success' => false,
                'message' => __('items.cannot_delete_has_movements'),
            ];
        }

        return DB::transaction(function () use ($item) {
            // Detach materials
            $item->materials()->detach();

            // Detach recipients
            $item->recipients()->detach();

            // Delete prices (cascade should handle this, but be explicit)
            $item->prices()->delete();

            // Delete the item
            $item->delete();

            return [
                'success' => true,
                'message' => __('items.deleted'),
            ];
        });
    }
}
