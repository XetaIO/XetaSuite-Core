<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Roles;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use XetaSuite\Models\Permission;
use XetaSuite\Models\Role;

class CreateRole
{
    /**
     * Create a new role.
     * Role can be global (site_id = null) or specific to one site.
     *
     * @param  array  $data  The data for the new role.
     *
     * @throws ValidationException
     */
    public function handle(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $permissions = isset($data['permissions']) && is_array($data['permissions'])
                ? Permission::whereIn('id', $data['permissions'])->get()
                : collect();

            try {
                $role = Role::create([
                    'name' => $data['name'],
                    'guard_name' => 'web',
                    'level' => $data['level'] ?? 0,
                    'site_id' => $data['site_id'] ?? null,
                ]);
            } catch (RoleAlreadyExists) {
                throw ValidationException::withMessages([
                    'name' => [__('roles.validation.name_unique')],
                ]);
            }

            if ($permissions->isNotEmpty()) {
                $role->syncPermissions($permissions);
            }

            return $role->load('permissions');
        });
    }
}
