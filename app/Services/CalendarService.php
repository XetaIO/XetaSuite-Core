<?php

declare(strict_types=1);

namespace XetaSuite\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use XetaSuite\Models\CalendarEvent;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Maintenance;

class CalendarService
{
    private const DEFAULT_EVENT_COLOR = '#465fff';

    /**
     * Get all calendar data for a date range.
     *
     * @return array<string, mixed>
     */
    public function getCalendarData(
        int $siteId,
        CarbonImmutable $start,
        CarbonImmutable $end,
        bool $showMaintenances = true,
        bool $showIncidents = true
    ): array {
        $events = $this->getCalendarEvents($siteId, $start, $end);

        if ($showMaintenances) {
            $events = $events->merge(
                $this->getMaintenanceEvents($siteId, $start, $end)
            );
        }

        if ($showIncidents) {
            $events = $events->merge(
                $this->getIncidentEvents($siteId, $start, $end)
            );
        }

        return $events->values()->all();
    }

    /**
     * Get today's events for the banner.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTodayEvents(
        int $siteId,
        bool $showMaintenances = true,
        bool $showIncidents = true
    ): array {
        $todayStart = CarbonImmutable::now()->startOfDay();
        $todayEnd = CarbonImmutable::now()->endOfDay();

        $events = $this->getCalendarData(
            $siteId,
            $todayStart,
            $todayEnd,
            $showMaintenances,
            $showIncidents
        );

        return collect($events)->filter(function ($event) use ($todayStart, $todayEnd) {
            return $this->isEventInDateRange($event, $todayStart, $todayEnd);
        })->values()->all();
    }

    /**
     * Get calendar events for a site within a date range.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function getCalendarEvents(int $siteId, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $events = CalendarEvent::query()
            ->with('eventCategory')
            ->forSite($siteId)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_at', '<=', $start)
                            ->where('end_at', '>=', $end);
                    });
            })
            ->get();

        return collect($events->map(fn (CalendarEvent $event) => $this->formatCalendarEvent($event))->all());
    }

    /**
     * Get maintenance events for a site within a date range.
     *
     * @param  array<int, string>  $statuses
     * @return Collection<int, array<string, mixed>>
     */
    private function getMaintenanceEvents(int $siteId, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $query = Maintenance::query()
            ->with(['material:id,name', 'site:id,name'])
            ->forCurrentSite()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('started_at', [$start, $end])
                    ->orWhereBetween('resolved_at', [$start, $end]);
            });

        $maintenances = $query->get();

        return collect($maintenances->map(fn (Maintenance $maintenance) => $this->formatMaintenanceEvent($maintenance))->all());
    }

    /**
     * Get incident events for a site within a date range.
     *
     * @param  array<int, string>  $statuses
     * @return Collection<int, array<string, mixed>>
     */
    private function getIncidentEvents(int $siteId, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $query = Incident::query()
            ->with(['material:id,name', 'site:id,name'])
            ->forCurrentSite()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('started_at', [$start, $end])
                    ->orWhereBetween('resolved_at', [$start, $end]);
            });

        $incidents = $query->get();

        return collect($incidents->map(fn (Incident $incident) => $this->formatIncidentEvent($incident))->all());
    }

    /**
     * Format a calendar event for the response.
     *
     * @return array<string, mixed>
     */
    private function formatCalendarEvent(CalendarEvent $event): array
    {
        return [
            'id' => 'event_' . $event->id,
            'type' => 'event',
            'resourceId' => $event->id,
            'title' => $event->title,
            'start' => $event->start_at->toIso8601String(),
            'end' => $event->end_at?->toIso8601String(),
            'allDay' => $event->all_day,
            'color' => $event->color,
            'extendedProps' => [
                'type' => 'event',
                'description' => $event->description,
                'category' => $event->eventCategory?->name,
                'categoryId' => $event->event_category_id,
                'createdBy' => $event->created_by_name,
            ],
        ];
    }

    /**
     * Format a maintenance for the calendar response.
     *
     * @return array<string, mixed>
     */
    private function formatMaintenanceEvent(Maintenance $maintenance): array
    {
        return [
            'id' => 'maintenance_' . $maintenance->id,
            'type' => 'maintenance',
            'resourceId' => $maintenance->id,
            'title' => $maintenance->description,
            'start' => $maintenance->started_at?->toIso8601String(),
            'end' => $maintenance->resolved_at?->toIso8601String(),
            'allDay' => false,
            'color' => $maintenance->status?->color() ?? self::DEFAULT_EVENT_COLOR,
            'editable' => false,
            'extendedProps' => [
                'type' => 'maintenance',
                'status' => $maintenance->status?->value,
                'statusLabel' => $maintenance->status?->label(),
                'material' => $maintenance->material?->name ?? $maintenance->material_name,
                'maintenanceType' => $maintenance->type?->value,
                'siteName' => $maintenance->site?->name,
            ],
        ];
    }

    /**
     * Format an incident for the calendar response.
     *
     * @return array<string, mixed>
     */
    private function formatIncidentEvent(Incident $incident): array
    {
        return [
            'id' => 'incident_' . $incident->id,
            'type' => 'incident',
            'resourceId' => $incident->id,
            'title' => $incident->description,
            'start' => $incident->started_at?->toIso8601String(),
            'end' => $incident->resolved_at?->toIso8601String(),
            'allDay' => false,
            'color' => $incident->status?->color() ?? self::DEFAULT_EVENT_COLOR,
            'editable' => false,
            'extendedProps' => [
                'type' => 'incident',
                'status' => $incident->status?->value,
                'statusLabel' => $incident->status?->label(),
                'material' => $incident->material?->name ?? $incident->material_name,
                'severity' => $incident->severity?->value,
                'severityLabel' => $incident->severity?->label(),
                'siteName' => $incident->site?->name,
            ],
        ];
    }

    /**
     * Check if an event falls within a date range.
     *
     * @param  array<string, mixed>  $event
     */
    private function isEventInDateRange(array $event, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        $eventStart = CarbonImmutable::parse($event['start']);
        $eventEnd = $event['end'] ? CarbonImmutable::parse($event['end']) : $eventStart;

        return $eventStart->between($start, $end)
            || $eventEnd->between($start, $end)
            || ($eventStart->lte($start) && $eventEnd->gte($end));
    }
}
