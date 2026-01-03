<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Company;
use XetaSuite\Models\Item;
use XetaSuite\Models\Material;
use XetaSuite\Models\User;

class ItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = Material::all();

        $emailDomain = config('app.demo_mode', false) ? 'xetasuite.demo' : 'xetasuite.test';
        $user = User::firstWhere('email', "admin@{$emailDomain}");

        foreach ($materials as $material) {
            Item::factory()
                    ->forSite($material->site_id)
                    ->fromCompany(Company::factory()->asItemProvider()->createdBy($user)->create())
                    ->withMaterials([$material->id])
                    ->createdBy($user)
                    ->count(random_int(2, 4))
                    ->create();
        }
    }
}
