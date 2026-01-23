<?php

declare(strict_types=1);

namespace XetaSuite\Http\Resources\V1\Calendar;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use XetaSuite\Http\Resources\V1\Users\UserResource;
use XetaSuite\Models\CalendarEvent;

/**
 * @mixin CalendarEvent
 */
class CalendarEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_category_id' => $this->event_category_id,
            'title' => $this->title,
            'description' => $this->description,
            'color' => $this->color,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'all_day' => $this->all_day,
            'category' => new EventCategoryResource($this->whenLoaded('eventCategory')),
            'created_by' => new UserResource($this->whenLoaded('createdBy')),
            'created_by_name' => $this->created_by_name,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
