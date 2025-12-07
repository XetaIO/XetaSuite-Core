<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\ItemMovements;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use XetaSuite\Models\ItemMovement;

class StoreItemMovementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ItemMovement::class)
            && $this->user()->can('view', $this->route('item'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['entry', 'exit'])],
            'quantity' => 'required|integer|min:1|max:999999',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'supplier_invoice_number' => 'nullable|string|max:100',
            'invoice_date' => 'nullable|date',
            'unit_price' => 'nullable|numeric|min:0|max:9999999.99',
            'notes' => 'nullable|string|max:2000',
            'movement_date' => 'nullable|date',
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
            'type' => __('items.movement_type'),
            'quantity' => __('items.quantity'),
            'supplier_id' => __('items.supplier'),
            'supplier_invoice_number' => __('items.invoice_number'),
            'invoice_date' => __('items.invoice_date'),
            'unit_price' => __('items.unit_price'),
            'notes' => __('items.notes'),
            'movement_date' => __('items.movement_date'),
        ];
    }
}
