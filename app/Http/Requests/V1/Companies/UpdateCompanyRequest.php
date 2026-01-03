<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Companies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use XetaSuite\Enums\Companies\CompanyType;

class UpdateCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('company'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('companies', 'name')->ignore($this->route('company'))],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'types' => ['sometimes', 'required', 'array', 'min:1'],
            'types.*' => ['required', 'string', Rule::in(CompanyType::values())],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('companies.name'),
            'description' => __('companies.description'),
            'types' => __('companies.types'),
            'types.*' => __('companies.type'),
            'email' => __('companies.email'),
            'phone' => __('companies.phone'),
            'address' => __('companies.address'),
        ];
    }
}
