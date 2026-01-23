<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Incidents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use XetaSuite\Enums\Incidents\IncidentSeverity;
use XetaSuite\Enums\Incidents\IncidentStatus;

class UpdateIncidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('incident'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $currentSiteId = session('current_site_id');

        return [
            'material_id' => [
                'sometimes',
                'integer',
                Rule::exists('materials', 'id')
                    ->where('site_id', $currentSiteId),
            ],
            'maintenance_id' => [
                'nullable',
                'integer',
                Rule::exists('maintenances', 'id')
                    ->where('site_id', $currentSiteId),
            ],
            'description' => ['sometimes', 'string', 'max:5000'],
            'severity' => [
                'sometimes',
                Rule::enum(IncidentSeverity::class),
            ],
            'status' => [
                'sometimes',
                Rule::enum(IncidentStatus::class),
            ],
            'started_at' => ['nullable', 'date'],
            'resolved_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'material_id.exists' => __('incidents.validation.material_not_found'),
            'maintenance_id.exists' => __('incidents.validation.maintenance_not_found'),
            'resolved_at.after_or_equal' => __('incidents.validation.resolved_after_started'),
        ];
    }
}
