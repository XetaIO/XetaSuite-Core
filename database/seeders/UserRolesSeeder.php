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
        // Example: Assign roles to users per site
        // This is a template - modify it according to your needs

        // Get all sites and users
        $sites = Site::all()->pluck('id')->toArray();
        $users = User::all();


        // Example assignment:
        // - First user is admin on all sites
        // - Other users get different roles on different sites
        $firstUser = $users->first();
        $otherUsers = $users->skip(1);

        //$role = Role::where('name', 'admin')->first();

        $firstUser->assignRolesToSites(Role::where('name', 'admin')->first(), $sites);
        $users->get(1)->assignRolesToSites(Role::where('name', 'manager')->first(), $sites);
        $users->get(2)->assignRolesToSites(Role::where('name', 'technician')->first(), $sites);
        $users->get(3)->assignRolesToSites(Role::where('name', 'operator')->first(), $sites);

        /*foreach ($otherUsers as $user) {
            $user->assignRolesToSites('manager', $sites);
        }

        foreach ($sites as $site) {
            // Assign admin role to the first user on all sites
            $adminRole = Role::where('name', 'admin')
                ->where('site_id', $site->id)
                ->first();

            if ($adminRole) {
                $firstUser->assignRole($adminRole);
            }

            // Assign different roles to other users
            $otherUsers->each(function (User $user, int $index) use ($sites, $site) {
                if ($site->is_headquarters) {
                    return true;
                }

                $roleNames = ['manager', 'technician', 'operator'];
                $roleName = $roleNames[$index % count($roleNames)];

                $role = Role::where('name', $roleName)
                    ->where('site_id', $site->id)
                    ->first();

                if ($role) {
                    $user->assignRole($role);
                }
            });
        }

        $this->command->info('User roles assigned successfully!');*/
    }
}
