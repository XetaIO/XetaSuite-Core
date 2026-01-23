<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Calendar;

use Illuminate\Foundation\Http\FormRequest;
use XetaSuite\Models\CalendarEvent;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CalendarEvent::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'event_category_id' => ['nullable', 'integer', 'exists:event_categories,id'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'all_day' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('validation.required', ['attribute' => __('calendar.events.title')]),
            'start_at.required' => __('validation.required', ['attribute' => __('calendar.events.startAt')]),
            'end_at.after_or_equal' => __('validation.after_or_equal', [
                'attribute' => __('calendar.events.endAt'),
                'date' => __('calendar.events.startAt'),
            ]),
        ];
    }
}
