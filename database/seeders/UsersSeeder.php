<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $headquarters = Site::where('is_headquarters', true)->first();

        $randomSiteIds = Site::inRandomOrder()
                ->where('is_headquarters', false)
                ->take(rand(1, Site::where('is_headquarters', false)->count()))
                ->pluck('id')
                ->toArray();

        // Admin - has access to all sites
        $admin = User::factory()->admin()->create([
            'username' => 'admin',
            'first_name' => 'Admin',
            'last_name' => 'Demo',
            'current_site_id' => $headquarters->id,
            'email' => "admin@xetasuite.demo",
        ]);
        $admin->sites()->sync(Site::pluck('id')->toArray());

        // Manager - has access to a regular site with more permissions
        $manager = User::factory()->create([
            'username' => 'manager',
            'first_name' => 'Manager',
            'last_name' => 'Demo',
            'current_site_id' => $randomSiteIds[0],
            'email' => "manager@xetasuite.demo",
        ]);
        $manager->sites()->sync($randomSiteIds);

        // Regular user - has access to a regular site with less permissions
        $user = User::factory()->create([
            'username' => 'operator',
            'first_name' => 'Operator',
            'last_name' => 'Demo',
            'current_site_id' => $randomSiteIds[0],
            'email' => "operator@xetasuite.demo",
        ]);
        $user->sites()->sync($randomSiteIds);
    }
}
