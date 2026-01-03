<?php

declare(strict_types=1);

namespace XetaSuite\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use XetaSuite\Models\User;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the list of users.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('user.viewAny');
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user): bool
    {
        return $user->can('user.view');
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->can('user.create');
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $auth, User $user): bool
    {
        if (! $auth->can('user.update')) {
            return false;
        }

        return $this->checkUserLevel($auth, $user);
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $auth, User $user): bool
    {
        if (! $auth->can('user.delete')) {
            return false;
        }

        return $this->checkUserLevel($auth, $user);
    }

    /**
     * Determine whether the user can restore the user.
     */
    public function restore(User $user): bool
    {
        return $user->can('user.restore');
    }

    /**
     * Determine whether the user can assign direct permission the user.
     */
    public function assignDirectPermission(User $user): bool
    {
        return $user->can('user.assignDirectPermission');
    }

    /**
     * Determine whether the user can assign sites the model.
     */
    public function assignSite(User $user): bool
    {
        return $user->can('user.assignSite');
    }

    /**
     * Check if the user level is sufficient to perform actions on another user.
     */
    protected function checkUserLevel(User $auth, User $user): bool
    {
        return $auth->level >= $user->level;
    }
}
