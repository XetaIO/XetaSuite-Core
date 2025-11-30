<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\ItemMovement;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\User;

class MaintenancesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $itemMovements = ItemMovement::with('item', 'item.materials')->where('type', 'exit')->get();
        $user = User::firstWhere('email', 'admin@xetasuite.test');

        foreach ($itemMovements as $itemMovement) {
            $maintenance = Maintenance::factory()
                ->forSite($itemMovement->item->site_id)
                ->forMaterial($itemMovement->item->materials->first())
                ->createdBy($user)
                ->create();

            $itemMovement->movable_type = Maintenance::class;
            $itemMovement->movable_id = $maintenance->id;
            $itemMovement->save();
        }
    }
}
