<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Calendar;

use XetaSuite\Models\CalendarEvent;

class UpdateCalendarEventDates
{
    /**
     * Update only dates of a calendar event (for drag & drop / resize).
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(CalendarEvent $event, array $data): CalendarEvent
    {
        $event->update([
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'] ?? $event->end_at,
            'all_day' => $data['all_day'] ?? $event->all_day,
        ]);

        return $event->fresh('eventCategory');
    }
}
