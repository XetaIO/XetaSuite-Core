<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Permission;
use XetaSuite\Models\Role;
use XetaSuite\Models\Site;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = require database_path('seeders/data/permissions.php');

        // Create / sync permissions (global, not team-specific)
        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // Define roles and their permissions
        $rolesDefinition = [
            'admin' => array_keys($permissions),

            'manager' => [
                'user.view', 'material.view', 'incident.view',
                'incident.update', 'maintenance.view',
            ],

            'technician' => [
                'incident.view', 'incident.update', 'maintenance.view',
            ],

            'operator' => [
                'incident.view', 'material.view', 'item.view',
            ],
        ];

        // Get all sites
        $sites = Site::all();

        // Create roles for each site
        foreach ($sites as $site) {
            foreach ($rolesDefinition as $roleName => $rolePermissions) {
                $role = Role::updateOrCreate(
                    [
                        'name' => $roleName,
                        'site_id' => $roleName === 'admin' ? null : $site->id,
                        'guard_name' => 'web',
                    ],
                    ['description' => ucfirst($roleName) . ' role for ' . $site->name]
                );

                // Sync permissions for this role and site
                $role->syncPermissions($rolePermissions);
            }
        }
    }
}
