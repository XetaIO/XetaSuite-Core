<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Items;

use XetaSuite\Jobs\CheckItemCriticalStock;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\User;

class CreateItemMovement
{
    /**
     * Create a new item movement (entry or exit).
     *
     * @param  array{type: string, quantity: int, supplier_id?: int, supplier_invoice_number?: string, invoice_date?: string, unit_price?: float, notes?: string, movement_date?: string, movable_type?: string, movable_id?: int}  $data
     */
    public function handle(Item $item, User $user, array $data): ItemMovement
    {
        $type = $data['type']; // 'entry' or 'exit'
        $quantity = (int) $data['quantity'];

        // Calculate total price if unit price is provided (default to 0)
        $unitPrice = isset($data['unit_price']) ? (float) $data['unit_price'] : 0.00;
        $totalPrice = $unitPrice * $quantity;

        $movement = ItemMovement::create([
            'item_id' => $item->id,
            'type' => $type,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'supplier_id' => $data['supplier_id'] ?? null,
            'supplier_name' => isset($data['supplier_id']) ? $item->supplier?->name : null,
            'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
            'invoice_date' => $data['invoice_date'] ?? null,
            'movable_type' => $data['movable_type'] ?? null,
            'movable_id' => $data['movable_id'] ?? null,
            'created_by_id' => $user->id,
            'created_by_name' => $user->full_name,
            'notes' => $data['notes'] ?? null,
            'movement_date' => $data['movement_date'] ?? now(),
        ]);

        // Note: item_entry_total/item_exit_total are updated by ItemMovementObserver
        // item_entry_count/item_exit_count are updated by xetaravel-counts package via getCountsConfig()

        // Check if we need to send critical stock alert (for exits only)
        if ($type === 'exit' && $item->number_critical_enabled) {
            $item->refresh();
            $currentStock = $item->item_entry_total - $item->item_exit_total;
            if ($currentStock <= $item->number_critical_minimum) {
                CheckItemCriticalStock::dispatch($item->id);
            }
        }

        return $movement;
    }
}
