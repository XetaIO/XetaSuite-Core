<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Roles;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\Role;

class DeleteRole
{
    /**
     * Delete a role.
     *
     * @param  Role  $role  The role to delete.
     * @return array{success: bool, message: string}
     */
    public function handle(Role $role): array
    {
        return DB::transaction(function () use ($role) {
            // Check if any users have this role
            $usersCount = $role->users()->count();

            if ($usersCount > 0) {
                return [
                    'success' => false,
                    'message' => __('roles.cannot_delete_role_with_users', ['count' => $usersCount]),
                ];
            }

            // Remove all permissions from the role
            $role->syncPermissions([]);

            // Delete the role
            $role->delete();

            return [
                'success' => true,
                'message' => __('roles.deleted'),
            ];
        });
    }
}
