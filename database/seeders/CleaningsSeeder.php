<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Material;
use XetaSuite\Models\User;

class CleaningsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = Material::inRandomOrder()->take(10)->get();

        $user = User::firstWhere('email', 'admin@xetasuite.test');

        foreach ($materials as $material) {
            Cleaning::factory()
                ->forSite($material->site_id)
                ->forMaterial($material)
                ->createdBy($user)
                ->count(random_int(5, 10))
                ->create();
        }
    }
}
