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
        $isDemoMode = config('app.demo_mode', false);
        $emailDomain = $isDemoMode ? 'xetasuite.demo' : 'xetasuite.test';

        // Admin - has access to all sites
        $admin = User::factory()->admin()->create([
            'username' => 'admin',
            'first_name' => 'Admin',
            'last_name' => 'Demo',
            'email' => "admin@{$emailDomain}",
        ]);
        $admin->sites()->sync(Site::pluck('id')->toArray());

        // Manager - has access to headquarters
        $headquarters = Site::where('is_headquarters', true)->first();
        $manager = User::factory()->create([
            'username' => 'manager',
            'first_name' => 'Manager',
            'last_name' => 'Demo',
            'email' => "manager@{$emailDomain}",
        ]);
        $manager->sites()->sync([$headquarters->id]);

        // Regular user - has access to a regular site
        $regularSite = Site::where('is_headquarters', false)->first();
        $user = User::factory()->create([
            'username' => 'user',
            'first_name' => 'User',
            'last_name' => 'Demo',
            'email' => "user@{$emailDomain}",
        ]);
        $user->sites()->sync([$regularSite?->id ?? $headquarters->id]);

        // Additional random users (only in non-demo mode for variety)
        if (! $isDemoMode) {
            $users = User::factory()->count(3)->create();
            $users->each(function (User $user) {
                $siteIds = Site::inRandomOrder()
                    ->where('is_headquarters', false)
                    ->take(rand(1, Site::where('is_headquarters', false)->count()))
                    ->pluck('id')
                    ->toArray();
                $user->sites()->sync($siteIds);
            });
        }
    }
}
