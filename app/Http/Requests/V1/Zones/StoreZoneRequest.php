<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Zones;

use Illuminate\Foundation\Http\FormRequest;
use XetaSuite\Models\Zone;

class StoreZoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Zone::class);
    }

    /**
     * Prepare the data for validation.
     * Force site_id to the user's current site.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'site_id' => $this->user()->current_site_id,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:zones,id'],
            'allow_material' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'site_id' => __('zones.site'),
            'name' => __('zones.name'),
            'parent_id' => __('zones.parent'),
            'allow_material' => __('zones.allow_material'),
        ];
    }
}
