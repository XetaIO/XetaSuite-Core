<?php

declare(strict_types=1);

namespace XetaSuite\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use XetaSuite\Models\Permission;
use XetaSuite\Models\User;

class PermissionPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * Only HQ users can manage permissions.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (! isOnHeadquarters()) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view the list of permissions.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('permission.viewAny');
    }

    /**
     * Determine whether the user can view the permission.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->can('permission.view');
    }

    /**
     * Determine whether the user can create permissions.
     */
    public function create(User $user): bool
    {
        return $user->can('permission.create');
    }

    /**
     * Determine whether the user can update the permission.
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->can('permission.update');
    }

    /**
     * Determine whether the user can delete the permission.
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('permission.delete');
    }
}
