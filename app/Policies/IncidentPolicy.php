<?php

declare(strict_types=1);

namespace XetaSuite\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use XetaSuite\Models\Incident;
use XetaSuite\Models\User;

class IncidentPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Disallow any creation, modification and deletion from HQ
        if (isOnHeadquarters() && in_array($ability, ['create', 'update', 'delete'], true)) {
            return false;
        }

        // HQ : can see an incident
        if (isOnHeadquarters() && $ability === 'view') {
            return $user->can('incident.view');
        }

        return null;
    }

    /**
     * Determine whether the user can view the list of incidents.
     * Incidents are filtered by current_site_id in the controller.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('incident.viewAny');
    }

    /**
     * Determine whether the user can view an incident.
     * User must be on the same site as the incident.
     */
    public function view(User $user, Incident $incident): bool
    {
        return $user->can('incident.view') && $incident->site_id === $user->current_site_id;
    }

    /**
     * Determine whether the user can create incidents.
     */
    public function create(User $user): bool
    {
        return $user->can('incident.create');
    }

    /**
     * Determine whether the user can update the incident.
     * User must be on the same site as the incident.
     */
    public function update(User $user, Incident $incident): bool
    {
        return $user->can('incident.update') && $incident->site_id === $user->current_site_id;
    }

    /**
     * Determine whether the user can delete the incident.
     * User must be on the same site as the incident.
     */
    public function delete(User $user, Incident $incident): bool
    {
        return $user->can('incident.delete') && $incident->site_id === $user->current_site_id;
    }
}
