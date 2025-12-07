<?php

declare(strict_types=1);

namespace XetaSuite\Actions\ItemMovements;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use XetaSuite\Actions\ItemPrices\CreateItemPrice;
use XetaSuite\Jobs\CheckItemCriticalStock;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;

class CreateItemMovement
{
    /**
     * Create a new item movement (entry or exit).
     *
     * @param  Item  $item  The item being moved.
     * @param  User  $user  The user performing the movement.
     * @param  array  $data  The data for the movement.
     */
    public function handle(Item $item, User $user, array $data): ItemMovement
    {
        if ($data['type'] === 'entry') {
            return $this->recordEntry($item, $user, $data);
        }

        // Exit movement
        return $this->recordExit($item, $user, $data);
    }

    /**
     * Record an entry (addition/restock) of items.
     *
     * @param  Item  $item  The item being moved.
     * @param  User  $user  The user performing the movement.
     * @param  array  $data  The data for the movement.
     */
    private function recordEntry(Item $item, User $user, array $data): ItemMovement
    {
        return DB::transaction(function () use ($item, $user, $data) {
            $unitPrice = isset($data['unit_price']) ? (float) $data['unit_price'] : 0.00;
            $quantity = (int) $data['quantity'];
            $supplier = isset($data['supplier_id']) ? Supplier::find($data['supplier_id']) : null;

            $movement = ItemMovement::create([
                'item_id' => $item->id,
                'type' => 'entry',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
                'supplier_id' => $supplier?->id,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'invoice_date' => isset($data['invoice_date']) ? new Carbon($data['invoice_date']) : null,
                'created_by_id' => $user->id,
                'created_by_name' => $user->full_name,
                'notes' => $data['notes'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
            ]);

            // Note: item_entry_total/item_exit_total are updated by ItemMovementObserver
            // item_entry_count/item_exit_count are updated by xetaravel-counts package via getCountsConfig()

            // If the price has changed, create a new price history
            if (! $item->current_price || $item->current_price != $unitPrice) {
                $data['supplier'] = $supplier;
                $data['current_price'] = $unitPrice;

                app(CreateItemPrice::class)->handle($item, $user, $data);
            }

            return $movement;
        });
    }

    /**
     * Record an exit (removal/consumption) of items.
     *
     * @param  Item  $item  The item being moved.
     * @param  User  $user  The user performing the movement.
     * @param  array  $data  The data for the movement.
     */
    private function recordExit(Item $item, User $user, array $data): ItemMovement
    {
        return DB::transaction(function () use ($item, $user, $data) {
            $quantity = (int) $data['quantity'];

            // Check stock availability
            if ($item->current_stock < $quantity) {
                throw new \Exception(__('items.insufficient_stock', ['item' => $item->name, 'current' => $item->current_stock]));
            }

            $relatedModel = isset($data['movable_type'], $data['movable_id']) ? app($data['movable_type'])->find($data['movable_id']) : null;

            $currentPrice = $item->current_price ?? 0.00;

            $movement = ItemMovement::create([
                'item_id' => $item->id,
                'type' => 'exit',
                'quantity' => $quantity,
                'unit_price' => $currentPrice,
                'total_price' => $quantity * $currentPrice,
                'movable_type' => $relatedModel ? get_class($relatedModel) : null,
                'movable_id' => $relatedModel?->id ?? null,
                'created_by_id' => $user->id,
                'created_by_name' => $user->full_name,
                'notes' => $data['notes'] ?? null,
                'movement_date' => $movementDate ?? now(),
            ]);

            // Note: item_entry_total/item_exit_total are updated by ItemMovementObserver
            // item_entry_count/item_exit_count are updated by xetaravel-counts package via getCountsConfig()

            // Check if we need to send critical stock alert jobs
            if ($item->number_critical_enabled) {
                $item->refresh();
                $currentStock = $item->item_entry_total - $item->item_exit_total;
                if ($currentStock <= $item->number_critical_minimum) {
                    CheckItemCriticalStock::dispatch($item->id);
                }
            }

            return $movement;
        });
    }
}
