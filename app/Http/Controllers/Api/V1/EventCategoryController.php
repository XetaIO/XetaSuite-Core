<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\EventCategories\CreateEventCategory;
use XetaSuite\Actions\EventCategories\DeleteEventCategory;
use XetaSuite\Actions\EventCategories\UpdateEventCategory;
use XetaSuite\Http\Requests\V1\Calendar\StoreEventCategoryRequest;
use XetaSuite\Http\Requests\V1\Calendar\UpdateEventCategoryRequest;
use XetaSuite\Http\Resources\V1\Calendar\EventCategoryResource;
use XetaSuite\Models\EventCategory;
use XetaSuite\Services\EventCategoryService;

class EventCategoryController extends Controller
{
    public function __construct(
        private readonly EventCategoryService $service
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EventCategory::class);

        $categories = $this->service->getPaginatedCategories([
            'search' => request('search'),
            'sort_by' => request('sort_by', 'name'),
            'sort_direction' => request('sort_direction', 'asc'),
        ]);

        return EventCategoryResource::collection($categories);
    }

    public function store(StoreEventCategoryRequest $request, CreateEventCategory $action): EventCategoryResource
    {
        $category = $action->handle($request->user(), $request->validated());

        return new EventCategoryResource($category);
    }

    public function show(EventCategory $eventCategory): EventCategoryResource
    {
        $this->authorize('view', $eventCategory);

        return new EventCategoryResource($eventCategory);
    }

    public function update(UpdateEventCategoryRequest $request, EventCategory $eventCategory, UpdateEventCategory $action): EventCategoryResource
    {
        $category = $action->handle($eventCategory, $request->user(), $request->validated());

        return new EventCategoryResource($category);
    }

    public function destroy(EventCategory $eventCategory, DeleteEventCategory $action): JsonResponse
    {
        $this->authorize('delete', $eventCategory);

        $action->handle($eventCategory);

        return response()->json(null, 204);
    }
}
