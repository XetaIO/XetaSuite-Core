<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Calendar;

use XetaSuite\Models\CalendarEvent;
use XetaSuite\Models\User;

class UpdateCalendarEvent
{
    /**
     * Update a calendar event.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(CalendarEvent $event, User $user, array $data): CalendarEvent
    {
        $event->update([
            'event_category_id' => $data['event_category_id'] ?? $event->event_category_id,
            'title' => $data['title'] ?? $event->title,
            'description' => $data['description'] ?? $event->description,
            'color' => $data['color'] ?? null,
            'start_at' => $data['start_at'] ?? $event->start_at,
            'end_at' => $data['end_at'] ?? $event->end_at,
            'all_day' => $data['all_day'] ?? $event->all_day,
        ]);

        return $event->fresh('eventCategory');
    }
}
