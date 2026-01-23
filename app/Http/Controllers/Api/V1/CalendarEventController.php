<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Calendar\CreateCalendarEvent;
use XetaSuite\Actions\Calendar\DeleteCalendarEvent;
use XetaSuite\Actions\Calendar\UpdateCalendarEvent;
use XetaSuite\Actions\Calendar\UpdateCalendarEventDates;
use XetaSuite\Http\Requests\V1\Calendar\StoreCalendarEventRequest;
use XetaSuite\Http\Requests\V1\Calendar\UpdateCalendarEventDatesRequest;
use XetaSuite\Http\Requests\V1\Calendar\UpdateCalendarEventRequest;
use XetaSuite\Http\Resources\V1\Calendar\CalendarEventResource;
use XetaSuite\Models\CalendarEvent;
use XetaSuite\Services\CalendarEventService;

class CalendarEventController extends Controller
{
    public function __construct(
        private readonly CalendarEventService $service
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CalendarEvent::class);

        $events = $this->service->getEvents([
            'start' => request('start'),
            'end' => request('end'),
            'category_id' => request('category_id'),
        ]);

        return CalendarEventResource::collection($events);
    }

    public function store(StoreCalendarEventRequest $request, CreateCalendarEvent $action): CalendarEventResource
    {
        $event = $action->handle($request->user(), $request->validated());

        return new CalendarEventResource($event);
    }

    public function show(CalendarEvent $calendarEvent): CalendarEventResource
    {
        $this->authorize('view', $calendarEvent);

        $calendarEvent->load(['eventCategory', 'createdBy']);

        return new CalendarEventResource($calendarEvent);
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendarEvent, UpdateCalendarEvent $action): CalendarEventResource
    {
        $event = $action->handle($calendarEvent, $request->user(), $request->validated());

        return new CalendarEventResource($event);
    }

    /**
     * Update only dates (for drag & drop / resize).
     */
    public function updateDates(UpdateCalendarEventDatesRequest $request, CalendarEvent $calendarEvent, UpdateCalendarEventDates $action): CalendarEventResource
    {
        $event = $action->handle($calendarEvent, $request->validated());

        return new CalendarEventResource($event);
    }

    public function destroy(CalendarEvent $calendarEvent, DeleteCalendarEvent $action): JsonResponse
    {
        $this->authorize('delete', $calendarEvent);

        $action->handle($calendarEvent);

        return response()->json(null, 204);
    }

    /**
     * Get available event categories for event creation/editing.
     */
    public function availableEventCategories(): JsonResponse
    {
        $this->authorize('viewAny', CalendarEvent::class);

        $search = request('search') ?: null;
        $categories = $this->service->getAvailableEventCategories($search);

        return response()->json([
            'data' => $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
            ]),
        ]);
    }
}
