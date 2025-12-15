<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Companies;

use Illuminate\Foundation\Http\FormRequest;
use XetaSuite\Models\Company;

class StoreCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Company::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
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
        ];
    }
}
