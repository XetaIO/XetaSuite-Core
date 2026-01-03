<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Role;
use XetaSuite\Models\User;

class UserRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::with('sites')->get();

        foreach ($users as $user) {
            foreach ($user->sites as $site) {
                // Ensure each user has a role for each of their sites
                $roleName = match (true) {
                    $user->username === 'admin' => 'admin',
                    $user->username === 'manager' => 'manager',
                    $user->username === 'operator' => 'operator',
                    default => null,
                };

                if ($roleName) {
                    $role = Role::where('name', $roleName)
                        ->where('site_id', $site->id)
                        ->first();

                    if ($role) {
                        $user->assignRolesToSites($role, [$site->id]);
                    }
                }
            }
        }
    }
}
