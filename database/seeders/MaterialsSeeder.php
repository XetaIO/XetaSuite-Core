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

        $emailDomain = config('app.demo_mode', false) ? 'xetasuite.demo' : 'xetasuite.test';
        $user = User::firstWhere('email', "admin@{$emailDomain}");

        foreach ($zones as $zone) {
            Material::factory()
                    ->forSite($zone->site_id)
                    ->forZone($zone)
                    ->createdBy($user)
                    ->count(random_int(2, 4))
                    ->create();
        }
    }
}
