<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Items;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \XetaSuite\Models\Item::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $siteId = session('current_site_id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('items')->where('site_id', $siteId),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('items')->where('site_id', $siteId),
            ],
            'description' => 'nullable|string|max:2000',
            'company_id' => 'nullable|integer|exists:companies,id',
            'company_reference' => 'nullable|string|max:100',
            'current_price' => 'nullable|numeric|min:0|max:9999999.99',
            'number_warning_enabled' => 'boolean',
            'number_warning_minimum' => 'nullable|integer|min:0',
            'number_critical_enabled' => 'boolean',
            'number_critical_minimum' => 'nullable|integer|min:0',
            'material_ids' => 'nullable|array',
            'material_ids.*' => [
                'integer',
                Rule::exists('materials', 'id')->where(function ($query) use ($siteId): void {
                    $query->whereIn('zone_id', function ($subQuery) use ($siteId): void {
                        $subQuery->select('id')
                            ->from('zones')
                            ->where('site_id', $siteId);
                    });
                }),
            ],
            'recipient_ids' => 'nullable|array',
            'recipient_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($siteId): void {
                    $query->whereIn('id', function ($subQuery) use ($siteId): void {
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
            'company_id' => __('items.company'),
            'company_reference' => __('items.company_reference'),
            'current_price' => __('items.current_price'),
            'number_warning_enabled' => __('items.number_warning_enabled'),
            'number_warning_minimum' => __('items.number_warning_minimum'),
            'number_critical_enabled' => __('items.number_critical_enabled'),
            'number_critical_minimum' => __('items.number_critical_minimum'),
            'material_ids' => __('items.materials'),
            'recipient_ids' => __('items.recipients'),
        ];
    }
}
