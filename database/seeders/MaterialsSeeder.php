<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Material;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

class MaterialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = Zone::where('allow_material', true)->get();
        $user = User::firstWhere('email', 'admin@xetasuite.test');

        foreach ($zones as $zone) {
            $zone->materials()->createMany(
                Material::factory()
                    ->inSiteAndZone($zone->site_id, $zone)
                    ->createdBy($user)
                    ->count(random_int(2, 4))
                    ->make()
                    ->toArray()
            );
        }
    }
}
