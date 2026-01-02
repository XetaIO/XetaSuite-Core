<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Role;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

class UserRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sites = Site::all();

        // Get demo users by username
        $admin = User::where('username', 'admin')->first();
        $manager = User::where('username', 'manager')->first();
        $user = User::where('username', 'user')->first();

        foreach ($sites as $site) {
            // Admin gets admin role on all sites
            if ($admin) {
                $adminRole = Role::where('name', 'admin')
                    ->where('site_id', $site->id)
                    ->first();

                if ($adminRole) {
                    $admin->assignRolesToSites($adminRole, [$site->id]);
                }
            }

            // Manager gets manager role on headquarters
            if ($manager && $site->is_headquarters) {
                $managerRole = Role::where('name', 'manager')
                    ->where('site_id', $site->id)
                    ->first();

                if ($managerRole) {
                    $manager->assignRolesToSites($managerRole, [$site->id]);
                }
            }

            // User gets operator role on non-headquarters sites
            if ($user && ! $site->is_headquarters) {
                $operatorRole = Role::where('name', 'operator')
                    ->where('site_id', $site->id)
                    ->first();

                if ($operatorRole) {
                    $user->assignRolesToSites($operatorRole, [$site->id]);
                }
            }
        }

        // Assign random roles to other users (non-demo users)
        $otherUsers = User::whereNotIn('username', ['admin', 'manager', 'user'])->get();
        $otherUsers->each(function (User $otherUser, int $index) use ($sites) {
            $sites->where('is_headquarters', false)->each(function (Site $site) use ($otherUser, $index) {
                $roleNames = ['manager', 'technician', 'operator'];
                $roleName = $roleNames[$index % count($roleNames)];

                $role = Role::where('name', $roleName)
                    ->where('site_id', $site->id)
                    ->first();

                if ($role) {
                    $otherUser->assignRolesToSites($role, [$site->id]);
                }
            });
        });
    }
}
