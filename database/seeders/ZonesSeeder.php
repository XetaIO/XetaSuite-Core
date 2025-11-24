<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Site;
use XetaSuite\Models\Zone;

class ZonesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sites = Site::where('is_headquarters', false)->get();

        foreach ($sites as $site) {
            // Parent Zones
            Zone::factory()
                ->forSite($site)
                ->count(random_int(1, 3))
                ->create();

            $site->refresh();

            // Child Zones
            foreach ($site->zones()->whereNull('parent_id')->get() as $parent) {
                Zone::factory()
                    ->forSite($site)
                    ->withParent($parent)
                    ->withAllowMaterial()
                    ->count(random_int(1, 3))
                    ->create();
            }
        }
    }
}
