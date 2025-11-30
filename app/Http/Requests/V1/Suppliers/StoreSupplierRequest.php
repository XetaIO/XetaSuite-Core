<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Suppliers;

use Illuminate\Foundation\Http\FormRequest;
use XetaSuite\Models\Supplier;

class StoreSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Supplier::class);
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
            'name' => __('suppliers.name'),
            'description' => __('suppliers.description'),
        ];
    }
}
