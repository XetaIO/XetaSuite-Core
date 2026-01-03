<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Cleanings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use XetaSuite\Enums\Cleanings\CleaningType;
use XetaSuite\Models\Cleaning;

class StoreCleaningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Cleaning::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'material_id' => [
                'required',
                'integer',
                Rule::exists('materials', 'id')->where('site_id', session('current_site_id')),
            ],
            'description' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(CleaningType::class)],
        ];
    }
}
