<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventDatesRequest extends FormRequest
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
            'start_at.required' => __('validation.required', ['attribute' => __('calendar.events.startAt')]),
            'end_at.after_or_equal' => __('validation.after_or_equal', [
                'attribute' => __('calendar.events.endAt'),
                'date' => __('calendar.events.startAt'),
            ]),
        ];
    }
}
