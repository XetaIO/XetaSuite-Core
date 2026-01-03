<?php

declare(strict_types=1);

namespace XetaSuite\Actions\ItemPrices;

use XetaSuite\Models\Item;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\User;

class CreateItemPrice
{
    /**
     * Create a new item price entry and update the item's current price.
     *
     * @param  Item  $item  The item for which the price is being created.
     * @param  User  $user  The user creating the price entry.
     * @param  array  $data  The data for the new price entry.
     */
    public function handle(Item $item, User $user, array $data): ItemPrice
    {
        $itemPrice = ItemPrice::create([
            'item_id' => $item->id,
            'company_id' => $data['company']?->id,
            'created_by_id' => $user->id,
            'created_by_name' => $user->full_name,
            'price' => $data['current_price'],
            'effective_date' => $data['effective_date'] ?? now(),
            'notes' => $data['notes'] ?? null,
        ]);

        // Update item's current price and company info
        $item->update([
            'current_price' => $data['current_price'],
            'company_id' => $data['company']?->id,
        ]);

        return $itemPrice;
    }
}
