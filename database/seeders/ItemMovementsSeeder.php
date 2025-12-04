<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Item;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\User;

class ItemMovementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = Item::with('supplier')->get();
        $user = User::firstWhere('email', 'admin@xetasuite.test');

        foreach ($items as $item) {
            // Create Entries Item Movements
            ItemMovement::factory()
                ->entry()
                ->forItem($item)
                ->fromSupplier($item->supplier->id)
                ->withQuantity(random_int(10, 50), (float) $item->purchase_price)
                ->createdBy($user)
                ->count(random_int(1, 2))
                ->create();

            // Create Exits Item Movements
            ItemMovement::factory()
                ->exit()
                ->forItem($item)
                ->withQuantity(random_int(2, 4), (float) $item->purchase_price)
                ->createdBy($user)
                ->count(random_int(1, 2))
                ->create();
        }
    }
}
