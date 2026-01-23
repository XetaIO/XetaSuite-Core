<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('calendar_event'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'event_category_id' => ['nullable', 'integer', 'exists:event_categories,id'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'start_at' => ['sometimes', 'required', 'date'],
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
