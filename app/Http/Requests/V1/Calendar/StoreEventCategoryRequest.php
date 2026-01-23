<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Calendar;

use Illuminate\Foundation\Http\FormRequest;
use XetaSuite\Models\EventCategory;

class StoreEventCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', EventCategory::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('calendar.categories.name')]),
            'color.regex' => __('validation.hex_color'),
        ];
    }
}
