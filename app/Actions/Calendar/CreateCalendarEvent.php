<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Calendar;

use Illuminate\Support\Facades\DB;
use XetaSuite\Models\CalendarEvent;
use XetaSuite\Models\User;

class CreateCalendarEvent
{
    /**
     * Create a new calendar event.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): CalendarEvent
    {
        return DB::transaction(function () use ($user, $data) {
            $event = CalendarEvent::create([
                'site_id' => $user->current_site_id,
                'event_category_id' => $data['event_category_id'] ?? null,
                'created_by_id' => $user->id,
                'created_by_name' => $user->full_name,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'color' => $data['color'] ?? null,
                'start_at' => $data['start_at'],
                'end_at' => $data['end_at'] ?? null,
                'all_day' => $data['all_day'] ?? false,
            ]);

            return $event->load(['eventCategory', 'createdBy']);
        });
    }
}
