<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Roles;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Permission;
use XetaSuite\Models\Role;

class CreateRole
{
    /**
     * Create a new role.
     *
     * @param  array  $data  The data for the new role.
     */
    public function handle(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            // Sync permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $permissions = Permission::whereIn('id', $data['permissions'])->get();
                $role->syncPermissions($permissions);
            }

            return $role->load('permissions');
        });
    }
}
