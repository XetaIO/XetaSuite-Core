<?php

declare(strict_types=1);

namespace XetaSuite\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use XetaSuite\Models\Setting;
use XetaSuite\Models\User;

class SettingPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * Settings can only be managed from headquarters.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Disallow any creation, modification and deletion from non-HQ
        if (! isOnHeadquarters() && in_array($ability, ['create', 'update', 'delete'], true)) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view the list of settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('setting.viewAny');
    }

    /**
     * Determine whether the user can view the setting.
     */
    public function view(User $user, Setting $setting): bool
    {
        return $user->can('setting.view');
    }

    /**
     * Determine whether the user can update the setting.
     */
    public function update(User $user, Setting $setting): bool
    {
        return $user->can('setting.update');
    }
}
