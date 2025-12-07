<?php

declare(strict_types=1);

namespace XetaSuite\Actions\ItemMovements;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\ItemMovement;

class UpdateItemMovement
{
    /**
     * Update an existing item movement and adjust item totals accordingly.
     *
     * @param  ItemMovement  $movement  The item movement to update.
     * @param  array  $data  The data to update the movement with.
     */
    public function handle(ItemMovement $movement, array $data): ItemMovement
    {
        return DB::transaction(function () use ($movement, $data) {
            $oldQuantity = $movement->quantity;
            $oldType = $movement->type;
            $item = $movement->item;

            // Prepare update data
            $updateData = [];

            if (isset($data['quantity'])) {
                $updateData['quantity'] = (int) $data['quantity'];
            }

            if (array_key_exists('unit_price', $data)) {
                $updateData['unit_price'] = $data['unit_price'] !== null ? (float) $data['unit_price'] : 0.00;
            }

            // Recalculate total price
            $quantity = $updateData['quantity'] ?? $movement->quantity;
            $unitPrice = $updateData['unit_price'] ?? $movement->unit_price;
            $updateData['total_price'] = $quantity * $unitPrice;

            if (array_key_exists('supplier_id', $data)) {
                $updateData['supplier_id'] = $data['supplier_id'];
            }

            if (array_key_exists('supplier_invoice_number', $data)) {
                $updateData['supplier_invoice_number'] = $data['supplier_invoice_number'];
            }

            if (array_key_exists('invoice_date', $data)) {
                $updateData['invoice_date'] = $data['invoice_date'];
            }

            if (array_key_exists('notes', $data)) {
                $updateData['notes'] = $data['notes'];
            }

            if (isset($data['movement_date'])) {
                $updateData['movement_date'] = $data['movement_date'];
            }

            $movement->update($updateData);

            // Adjust item totals if quantity changed
            if (isset($data['quantity']) && $oldQuantity !== $data['quantity']) {
                $difference = $data['quantity'] - $oldQuantity;

                if ($oldType === 'entry') {
                    $item->increment('item_entry_total', $difference);
                } else {
                    $item->increment('item_exit_total', $difference);
                }
            }

            return $movement->fresh();
        });
    }
}
