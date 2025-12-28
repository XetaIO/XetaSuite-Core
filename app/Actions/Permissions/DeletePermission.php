<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Permissions;

use XetaSuite\Models\Permission;

class DeletePermission
{
    /**
     * Delete a permission.
     *
     * @return array{success: bool, message: string}
     */
    public function handle(Permission $permission): array
    {
        // Check if permission is assigned to any roles
        if ($permission->roles()->count() > 0) {
            return [
                'success' => false,
                'message' => __('permissions.errors.hasRoles'),
            ];
        }

        $permission->delete();

        return [
            'success' => true,
            'message' => __('permissions.messages.deleted', ['name' => $permission->name]),
        ];
    }
}
