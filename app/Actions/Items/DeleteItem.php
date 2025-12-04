<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Items;

use XetaSuite\Models\Item;

class DeleteItem
{
    /**
     * Delete an item.
     *
     * @return array{success: bool, message?: string}
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
    }
}
