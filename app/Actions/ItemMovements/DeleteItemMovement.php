<?php

declare(strict_types=1);

namespace XetaSuite\Actions\ItemMovements;

use XetaSuite\Models\ItemMovement;

class DeleteItemMovement
{
    /**
     *  Delete an item movement and adjust item totals accordingly.
     *
     * @param  ItemMovement  $movement  The item movement to delete.
     * @return array{success: bool}
     */
    public function handle(ItemMovement $movement): array
    {
        // Delete the movement
        $movement->delete();

        return ['success' => true];
    }
}
