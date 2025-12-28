<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Roles;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Permission;
use XetaSuite\Models\Role;

class UpdateRole
{
    /**
     * Update an existing role.
     *
     * @param  Role  $role  The role to update.
     * @param  array  $data  The data to update.
     */
    public function handle(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            if (isset($data['name'])) {
                $role->update([
                    'name' => $data['name'],
                ]);
            }

            // Sync permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $permissions = Permission::whereIn('id', $data['permissions'])->get();
                $role->syncPermissions($permissions);
            }

            return $role->load('permissions');
        });
    }
}
