<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Items;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('item'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $siteId = $this->user()->current_site_id;
        $itemId = $this->route('item')->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('items')->where('site_id', $siteId)->ignore($itemId),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('items')->where('site_id', $siteId)->ignore($itemId),
            ],
            'description' => 'nullable|string|max:2000',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'supplier_reference' => 'nullable|string|max:100',
            'current_price' => 'nullable|numeric|min:0|max:9999999.99',
            'currency' => 'nullable|string|size:3',
            'number_warning_enabled' => 'boolean',
            'number_warning_minimum' => 'nullable|integer|min:0',
            'number_critical_enabled' => 'boolean',
            'number_critical_minimum' => 'nullable|integer|min:0',
            'material_ids' => 'nullable|array',
            'material_ids.*' => [
                'integer',
                Rule::exists('materials', 'id')->where(function ($query) use ($siteId) {
                    $query->whereIn('zone_id', function ($subQuery) use ($siteId) {
                        $subQuery->select('id')
                            ->from('zones')
                            ->where('site_id', $siteId);
                    });
                }),
            ],
            'recipient_ids' => 'nullable|array',
            'recipient_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($siteId) {
                    $query->whereIn('id', function ($subQuery) use ($siteId) {
                        $subQuery->select('user_id')
                            ->from('site_user')
                            ->where('site_id', $siteId);
                    });
                }),
            ],
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
            'name' => __('items.name'),
            'reference' => __('items.reference'),
            'description' => __('items.description'),
            'supplier_id' => __('items.supplier'),
            'supplier_reference' => __('items.supplier_reference'),
            'current_price' => __('items.current_price'),
            'currency' => __('items.currency'),
            'number_warning_enabled' => __('items.number_warning_enabled'),
            'number_warning_minimum' => __('items.number_warning_minimum'),
            'number_critical_enabled' => __('items.number_critical_enabled'),
            'number_critical_minimum' => __('items.number_critical_minimum'),
            'material_ids' => __('items.materials'),
            'recipient_ids' => __('items.recipients'),
        ];
    }
}
