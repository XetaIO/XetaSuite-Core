<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Zones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use XetaSuite\Models\Zone;

class UpdateZoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('zone'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /** @var Zone $zone */
        $zone = $this->route('zone');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('zones', 'id')->where('site_id', $zone->site_id),
            ],
            'allow_material' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('zones.name'),
            'parent_id' => __('zones.parent'),
            'allow_material' => __('zones.allow_material'),
        ];
    }
}
