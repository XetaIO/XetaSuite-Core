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
     * Note: site_id cannot be changed after creation.
     *
     * @param  Role  $role  The role to update.
     * @param  array  $data  The data to update.
     */
    public function handle(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (array_key_exists('level', $data)) {
                $updateData['level'] = $data['level'];
            }

            if (! empty($updateData)) {
                $role->update($updateData);
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
