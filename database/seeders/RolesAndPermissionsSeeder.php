<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use XetaSuite\Models\Permission;
use XetaSuite\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = require database_path('seeders/data/permissions.php');

        // Create / sync permissions
        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // Define roles
        $roles = [
            'admin' => array_keys($permissions),

            'manager' => [
                'users.view', 'materials.view', 'incidents.view',
                'incidents.update', 'incidents.close',
                'maintenance.view',
            ],

            'technician' => [
                'incidents.view', 'incidents.update', 'maintenance.view',
            ],

            'operator' => [
                'incidents.view', 'materials.view', 'items.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );

            $role->syncPermissions($rolePermissions);
        }
    }
}
