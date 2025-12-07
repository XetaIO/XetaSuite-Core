<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Items;

use Illuminate\Support\Facades\DB;
use XetaSuite\Actions\ItemPrices\CreateItemPrice;
use XetaSuite\Models\Item;
use XetaSuite\Models\User;

class UpdateItem
{
    /**
     * Update an existing item with new data.
     *
     * @param  Item  $item  The item to be updated.
     * @param  User  $user  The user performing the update.
     * @param  array  $data  The data to update the item with.
     */
    public function handle(Item $item, User $user, array $data): Item
    {
        return DB::transaction(function () use ($item, $user, $data) {
            $oldPrice = (float) $item->current_price;
            $oldSupplierId = $item->supplier_id;
            $newPrice = isset($data['current_price']) ? (float) $data['current_price'] : $oldPrice;
            $newSupplierId = $data['supplier_id'] ?? $oldSupplierId;
            // Check if price has changed
            $priceChanged = abs($newPrice - $oldPrice) > 0.001;
            $supplierChanged = $newSupplierId !== $oldSupplierId;

            $item->update([
                'edited_by_id' => $user->id,
                'name' => $data['name'] ?? $item->name,
                'reference' => array_key_exists('reference', $data) ? $data['reference'] : $item->reference,
                'description' => array_key_exists('description', $data) ? $data['description'] : $item->description,
                'supplier_id' => $newSupplierId,
                'supplier_reference' => array_key_exists('supplier_reference', $data) ? $data['supplier_reference'] : $item->supplier_reference,
                'current_price' => $newPrice,
                'currency' => $data['currency'] ?? $item->currency,
                'number_warning_enabled' => $data['number_warning_enabled'] ?? $item->number_warning_enabled,
                'number_warning_minimum' => $data['number_warning_minimum'] ?? $item->number_warning_minimum,
                'number_critical_enabled' => $data['number_critical_enabled'] ?? $item->number_critical_enabled,
                'number_critical_minimum' => $data['number_critical_minimum'] ?? $item->number_critical_minimum,
            ]);

            // Sync materials if provided
            if (array_key_exists('material_ids', $data)) {
                $item->materials()->sync($data['material_ids'] ?? []);
            }

            // Sync recipients if provided
            if (array_key_exists('recipient_ids', $data)) {
                $item->recipients()->sync($data['recipient_ids'] ?? []);
            }

            // Record price change in history via queue if price or supplier changed
            if ($priceChanged || $supplierChanged) {
                $data['notes'] = $priceChanged ? __('items.price_updated') : __('items.supplier_changed');

                app(CreateItemPrice::class)->handle($item, $user, $data);
            }

            return $item->fresh();
        });
    }
}
