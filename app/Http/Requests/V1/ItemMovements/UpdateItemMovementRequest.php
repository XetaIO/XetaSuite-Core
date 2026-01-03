<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\ItemMovements;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemMovementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('movement'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => 'sometimes|required|integer|min:1|max:999999',
            'company_id' => 'nullable|integer|exists:companies,id',
            'company_invoice_number' => 'nullable|string|max:100',
            'invoice_date' => 'nullable|date',
            'unit_price' => 'nullable|numeric|min:0|max:9999999.99',
            'notes' => 'nullable|string|max:2000',
            'movement_date' => 'sometimes|required|date',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'quantity' => __('items.quantity'),
            'company_id' => __('items.company'),
            'company_invoice_number' => __('items.invoice_number'),
            'invoice_date' => __('items.invoice_date'),
            'unit_price' => __('items.unit_price'),
            'notes' => __('items.notes'),
            'movement_date' => __('items.movement_date'),
        ];
    }
}
