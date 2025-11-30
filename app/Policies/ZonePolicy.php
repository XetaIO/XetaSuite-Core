<?php

declare(strict_types=1);

namespace XetaSuite\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

class ZonePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the list of zones.
     * Used for accessing the zones table/index page.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('zone.viewAny') && settings('zone_manage_enabled', true);
    }

    /**
     * Determine whether the user can create zones.
     */
    public function create(User $user): bool
    {
        return $user->can('zone.create') && settings('zone_create_enabled', true);
    }

    /**
     * Determine whether the user can update the zone.
     */
    public function update(User $user, Zone $zone): bool
    {
        if (! $user->can('zone.update')) {
            return false;
        }

        return $this->belongsToUserSite($zone);
    }

    /**
     * Determine whether the user can delete the zone.
     */
    public function delete(User $user, Zone $zone): bool
    {
        if (! $user->can('zone.delete')) {
            return false;
        }

        return $this->belongsToUserSite($zone);
    }

    /**
     * Check if the zone belongs to the user's current site.
     */
    protected function belongsToUserSite(Zone $zone): bool
    {
        return $zone->site_id === getPermissionsTeamId();
    }
}
