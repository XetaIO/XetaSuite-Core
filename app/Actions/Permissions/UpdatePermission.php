<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Permissions;

use XetaSuite\Models\Permission;

class UpdatePermission
{
    /**
     * Update an existing permission.
     *
     * @param  array{name?: string}  $data
     */
    public function handle(Permission $permission, array $data): Permission
    {
        if (isset($data['name'])) {
            $permission->name = $data['name'];
        }

        $permission->save();

        return $permission->fresh();
    }
}
