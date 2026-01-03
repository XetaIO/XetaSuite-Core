<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Settings;

use Illuminate\Foundation\Http\FormRequest;
use XetaSuite\Models\Setting;

class UpdateSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('setting'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /** @var Setting $setting */
        $setting = $this->route('setting');

        // Dynamic validation based on setting key
        return match ($setting->key) {
            'login_enabled' => [
                'value' => ['required', 'boolean'],
            ],
            'currency' => [
                'value' => ['required', 'string', 'size:3'],
            ],
            'currency_symbol' => [
                'value' => ['required', 'string', 'max:5'],
            ],
            default => [
                'value' => ['required'],
            ],
        };
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'value' => __('settings.value'),
        ];
    }
}
