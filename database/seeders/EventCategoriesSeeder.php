<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\EventCategory;
use XetaSuite\Models\Site;

class EventCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sites = Site::where('is_headquarters', false)->get();

        foreach ($sites as $site) {
            for ($i = 2; $i < 8; $i++) {
                EventCategory::factory()
                    ->forSite($site->id)
                    ->withColor('#' . dechex(rand(0x000000, 0xFFFFFF)))
                    ->create();
            }
        }
    }
}
