<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Items;

use XetaSuite\Jobs\RecordItemPriceChange;
use XetaSuite\Models\Item;
use XetaSuite\Models\User;

class UpdateItem
{
    /**
     * Update an existing item.
     *
     * @param  array{name?: string, reference?: string, description?: string, supplier_id?: int, supplier_reference?: string, purchase_price?: float, currency?: string, number_warning_enabled?: bool, number_warning_minimum?: int, number_critical_enabled?: bool, number_critical_minimum?: int, material_ids?: array, recipient_ids?: array}  $data
     */
    public function handle(Item $item, User $user, array $data): Item
    {
        $oldPrice = (float) $item->purchase_price;
        $oldSupplierId = $item->supplier_id;
        $newPrice = isset($data['purchase_price']) ? (float) $data['purchase_price'] : $oldPrice;
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
            'purchase_price' => $newPrice,
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
            RecordItemPriceChange::dispatch(
                $item->id,
                $newPrice,
                $newSupplierId,
                $user->id,
                $user->full_name,
                $data['currency'] ?? $item->currency,
                $priceChanged ? __('items.price_updated') : __('items.supplier_changed')
            );
        }

        return $item->fresh();
    }
}
