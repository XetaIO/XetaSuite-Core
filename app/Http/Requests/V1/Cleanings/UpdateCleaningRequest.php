<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Cleanings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use XetaSuite\Enums\Cleanings\CleaningType;
use XetaSuite\Models\Cleaning;

class UpdateCleaningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $cleaning = $this->route('cleaning');

        return $cleaning instanceof Cleaning
            && $this->user()->can('update', $cleaning);
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
                'sometimes',
                'required',
                'integer',
                Rule::exists('materials', 'id')->where('site_id', $this->user()->current_site_id),
            ],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'type' => ['sometimes', 'required', Rule::enum(CleaningType::class)],
        ];
    }
}
