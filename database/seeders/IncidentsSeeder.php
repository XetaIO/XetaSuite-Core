<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\User;

class IncidentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $maintenances = Maintenance::all();
        $user = User::firstWhere('email', 'admin@xetasuite.test');

        foreach ($maintenances as $maintenance) {
            $incidents = Incident::factory()
                    ->forSite($maintenance->site_id)
                    ->forMaterial($maintenance->material_id)
                    ->withMaintenance($maintenance)
                    ->reportedBy($user)
                    ->count(random_int(1, 2))
                    ->create();
        }
    }
}
