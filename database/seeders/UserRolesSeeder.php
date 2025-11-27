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
        // Assign roles to users per site
        // Get all sites and users
        $sites = Site::all();
        $users = User::all();

        // Assignment:
        // - First user is admin on all sites
        // - Other users get different roles on different sites
        $firstUser = $users->first();
        $otherUsers = $users->skip(1);

        foreach ($sites as $site) {
            // Assign admin role to the first user on all sites
            $adminRole = Role::where('name', 'admin')
                ->where('site_id', $site->id)
                ->first();

            if ($adminRole) {
                $firstUser->assignRolesToSites($adminRole, [$site->id]);
            }

            // Assign different roles to other users
            $otherUsers->each(function (User $user, int $index) use ($site) {
                if ($site->is_headquarters) {
                    return true;
                }

                $roleNames = ['manager', 'technician', 'operator'];
                $roleName = $roleNames[$index % count($roleNames)];

                $role = Role::where('name', $roleName)
                    ->where('site_id', $site->id)
                    ->first();

                if ($role) {
                    $user->assignRolesToSites($role, [$site->id]);
                }
            });
        }
    }
}
