<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Items;

use Illuminate\Support\Facades\DB;
use XetaSuite\Actions\ItemPrices\CreateItemPrice;
use XetaSuite\Models\Item;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;

class CreateItem
{
    /**
     * Create a new item.
     *
     * @param  User  $user  The user creating the item.
     * @param  array  $data  The data for creating the item.
     */
    public function handle(User $user, array $data): Item
    {
        return DB::transaction(function () use ($user, $data) {
            $supplier = isset($data['supplier_id']) ? Supplier::find($data['supplier_id']) : null;

            $item = Item::create([
                'site_id' => $user->current_site_id,
                'created_by_id' => $user->id,
                'name' => $data['name'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'supplier_id' => $supplier?->id,
                'supplier_reference' => $data['supplier_reference'] ?? null,
                'current_price' => $data['current_price'] ?? 0,
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

            // Create initial price history if current_price is set
            if (($data['current_price'] ?? 0) > 0) {
                $data['notes'] = __('items.initial_price');
                $data['supplier'] = $supplier;

                app(CreateItemPrice::class)->handle($item, $user, $data);
            }

            return $item;
        });
    }
}
