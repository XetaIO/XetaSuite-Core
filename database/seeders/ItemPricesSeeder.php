<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemPrice;
use XetaSuite\Models\User;

class ItemPricesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = Item::all();
        $user = User::firstWhere('email', "admin@xetasuite.demo");

        foreach ($items as $item) {
            $prices = random_int(1, 3);

            // Create item prices with some variation around the item's purchase price
            for ($i = 0; $i < $prices; $i++) {
                ItemPrice::factory()
                    ->forItem($item)
                    ->fromCompany($item->company_id)
                    ->withPrice((float) $item->current_price, true)
                    ->createdBy($user)
                    ->create();
            }
        }
    }
}
