<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'current_password.required' => __('validation.required', ['attribute' => __('user.current_password')]),
            'current_password.current_password' => __('user.password_incorrect'),
            'password.required' => __('validation.required', ['attribute' => __('user.new_password')]),
            'password.confirmed' => __('validation.confirmed', ['attribute' => __('user.new_password')]),
        ];
    }
}
