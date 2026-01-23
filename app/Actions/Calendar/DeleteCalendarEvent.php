<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Calendar;

use XetaSuite\Models\CalendarEvent;

class DeleteCalendarEvent
{
    /**
     * Delete a calendar event.
     */
    public function handle(CalendarEvent $event): void
    {
        $event->delete();
    }
}
