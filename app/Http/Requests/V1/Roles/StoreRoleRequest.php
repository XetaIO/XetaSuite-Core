<?php

declare(strict_types=1);

namespace XetaSuite\Http\Requests\V1\Roles;

use Illuminate\Foundation\Http\FormRequest;
use XetaSuite\Models\Role;

class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Role::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'level' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'site_id' => ['sometimes', 'nullable', 'integer', 'exists:sites,id'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('roles.validation.name_required'),
            'name.unique' => __('roles.validation.name_unique'),
            'permissions.array' => __('roles.validation.permissions_array'),
            'permissions.*.exists' => __('roles.validation.permission_exists'),
        ];
    }
}
