<?php

declare(strict_types=1);

namespace XetaSuite\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

class SitePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user is on the headquarters site before checking specific abilities.
     * Sites can only be managed from the headquarters site.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Sites can only be managed from the headquarters site
        if (! isOnHeadquarters()) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view the list of sites.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('site.viewAny');
    }

    /**
     * Determine whether the user can view the site.
     */
    public function view(User $user, Site $site): bool
    {
        return $user->can('site.view');
    }

    /**
     * Determine whether the user can create sites.
     */
    public function create(User $user): bool
    {
        return $user->can('site.create');
    }

    /**
     * Determine whether the user can update the site.
     */
    public function update(User $user, Site $site): bool
    {
        return $user->can('site.update');
    }

    /**
     * Determine whether the user can delete the site.
     */
    public function delete(User $user, Site $site): bool
    {
        return $user->can('site.delete');
    }
}
