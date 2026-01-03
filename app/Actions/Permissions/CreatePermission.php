<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Permissions;

use XetaSuite\Models\Permission;

class CreatePermission
{
    /**
     * Create a new permission.
     *
     * @param  array{name: string}  $data
     */
    public function handle(array $data): Permission
    {
        return Permission::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);
    }
}
