<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use XetaSuite\Services\CalendarService;

class CalendarController extends Controller
{
    public function __construct(
        private readonly CalendarService $calendarService
    ) {
    }

    /**
     * Get all calendar data (events, maintenances, incidents) for a date range.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $siteId = $user->current_site_id;

        $start = request('start') ? CarbonImmutable::parse(request('start')) : now()->startOfMonth();
        $end = request('end') ? CarbonImmutable::parse(request('end')) : now()->endOfMonth();

        $showMaintenances = filter_var(request('show_maintenances', true), FILTER_VALIDATE_BOOLEAN);
        $showIncidents = filter_var(request('show_incidents', true), FILTER_VALIDATE_BOOLEAN);

        $events = $this->calendarService->getCalendarData(
            $siteId,
            $start,
            $end,
            $showMaintenances,
            $showIncidents
        );

        return response()->json(['events' => $events]);
    }

    /**
     * Get today's events for the banner.
     */
    public function today(): JsonResponse
    {
        $user = auth()->user();
        $siteId = $user->current_site_id;

        $showMaintenances = filter_var(request('show_maintenances', true), FILTER_VALIDATE_BOOLEAN);
        $showIncidents = filter_var(request('show_incidents', true), FILTER_VALIDATE_BOOLEAN);

        $events = $this->calendarService->getTodayEvents(
            $siteId,
            $showMaintenances,
            $showIncidents
        );

        return response()->json($events);
    }
}
