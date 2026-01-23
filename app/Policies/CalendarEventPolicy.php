<?php

declare(strict_types=1);

namespace XetaSuite\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use XetaSuite\Models\CalendarEvent;
use XetaSuite\Models\User;

class CalendarEventPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Disallow any creation, modification and deletion from HQ
        if (isOnHeadquarters() && in_array($ability, ['create', 'update', 'delete'], true)) {
            return false;
        }

        // HQ : can see a calendar event
        if (isOnHeadquarters() && $ability === 'view') {
            return $user->can('calendarEvent.view');
        }

        return null;
    }

    /**
     * Determine whether the user can view the list of calendar events.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('calendarEvent.viewAny');
    }

    /**
     * Determine whether the user can view a calendar event.
     */
    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->can('calendarEvent.view')
            && $calendarEvent->site_id === $user->current_site_id;
    }

    /**
     * Determine whether the user can create calendar events.
     */
    public function create(User $user): bool
    {
        return $user->can('calendarEvent.create');
    }

    /**
     * Determine whether the user can update the calendar event.
     */
    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->can('calendarEvent.update')
            && $calendarEvent->site_id === $user->current_site_id;
    }

    /**
     * Determine whether the user can delete the calendar event.
     */
    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->can('calendarEvent.delete')
            && $calendarEvent->site_id === $user->current_site_id;
    }
}
