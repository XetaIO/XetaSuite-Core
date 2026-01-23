<?php

declare(strict_types=1);

namespace XetaSuite\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use XetaSuite\Models\EventCategory;
use XetaSuite\Models\User;

class EventCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Disallow all actions from HQ
        if (isOnHeadquarters()) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view the list of event categories.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('eventCategory.viewAny');
    }

    /**
     * Determine whether the user can view an event category.
     */
    public function view(User $user, EventCategory $eventCategory): bool
    {
        return $user->can('eventCategory.view') && $eventCategory->site_id === $user->current_site_id;
    }

    /**
     * Determine whether the user can create event categories.
     */
    public function create(User $user): bool
    {
        return $user->can('eventCategory.create');
    }

    /**
     * Determine whether the user can update the event category.
     */
    public function update(User $user, EventCategory $eventCategory): bool
    {
        return $user->can('eventCategory.update') && $eventCategory->site_id === $user->current_site_id;
    }

    /**
     * Determine whether the user can delete the event category.
     */
    public function delete(User $user, EventCategory $eventCategory): bool
    {
        return $user->can('eventCategory.delete') && $eventCategory->site_id === $user->current_site_id;
    }
}
