<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use XetaSuite\Models\CalendarEvent;
use XetaSuite\Models\EventCategory;

class CalendarEventService
{
    /**
     * Get calendar events for the current user's site with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, CalendarEvent>
     */
    public function getEvents(array $filters = []): Collection
    {
        $user = auth()->user();

        $query = CalendarEvent::query()
            ->with('eventCategory')
            ->where('site_id', $user->current_site_id);

        if (! empty($filters['start'])) {
            $query->where('start_at', '>=', $filters['start']);
        }

        if (! empty($filters['end'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('end_at', '<=', $filters['end'])
                    ->orWhereNull('end_at');
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('event_category_id', $filters['category_id']);
        }

        return $query->orderBy('start_at')->get();
    }

    /**
     * Get available event categories for event creation/editing.
     *
     * @return SupportCollection<int, EventCategory>
     */
    public function getAvailableEventCategories(?string $search = null): SupportCollection
    {
        $user = auth()->user();

        return EventCategory::query()
            ->where('site_id', $user->current_site_id)
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'color']);
    }
}
