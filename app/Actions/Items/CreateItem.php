<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Items;

use XetaSuite\Models\Item;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\User;

class CreateItem
{
    /**
     * Create a new item.
     *
     * @param  array{name: string, reference?: string, description?: string, supplier_id?: int, supplier_reference?: string, purchase_price?: float, currency?: string, number_warning_enabled?: bool, number_warning_minimum?: int, number_critical_enabled?: bool, number_critical_minimum?: int, material_ids?: array, recipient_ids?: array}  $data
     */
    public function handle(User $user, array $data): Item
    {
        $item = Item::create([
            'site_id' => $user->current_site_id,
            'created_by_id' => $user->id,
            'created_by_name' => $user->full_name,
            'name' => $data['name'],
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'supplier_reference' => $data['supplier_reference'] ?? null,
            'purchase_price' => $data['purchase_price'] ?? 0,
            'currency' => $data['currency'] ?? 'EUR',
            'number_warning_enabled' => $data['number_warning_enabled'] ?? false,
            'number_warning_minimum' => $data['number_warning_minimum'] ?? 0,
            'number_critical_enabled' => $data['number_critical_enabled'] ?? false,
            'number_critical_minimum' => $data['number_critical_minimum'] ?? 0,
        ]);

        // Attach materials if provided
        if (! empty($data['material_ids'])) {
            $item->materials()->attach($data['material_ids']);
        }

        // Attach recipients for critical alerts if provided
        if (! empty($data['recipient_ids'])) {
            $item->recipients()->attach($data['recipient_ids']);
        }

        // Create initial price history if purchase_price is set
        if (($data['purchase_price'] ?? 0) > 0) {
            ItemPrice::create([
                'item_id' => $item->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'supplier_name' => $item->supplier?->name,
                'created_by_id' => $user->id,
                'created_by_name' => $user->full_name,
                'price' => $data['purchase_price'],
                'effective_date' => now(),
                'currency' => $data['currency'] ?? 'EUR',
                'notes' => __('items.initial_price'),
            ]);
        }

        return $item;
    }
}
