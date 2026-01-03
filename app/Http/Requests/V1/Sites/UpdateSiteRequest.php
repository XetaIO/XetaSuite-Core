<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Sites;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('site'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_headquarters' => ['sometimes', 'boolean'],
            'email' => ['nullable', 'email', 'max:100'],
            'office_phone' => ['nullable', 'string', 'max:20'],
            'cell_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'manager_ids' => ['nullable', 'array'],
            'manager_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('sites.name'),
            'is_headquarters' => __('sites.is_headquarters'),
            'email' => __('sites.email'),
            'office_phone' => __('sites.office_phone'),
            'cell_phone' => __('sites.cell_phone'),
            'address' => __('sites.address'),
            'zip_code' => __('sites.zip_code'),
            'city' => __('sites.city'),
            'country' => __('sites.country'),
            'manager_ids' => __('sites.managers'),
        ];
    }
}
