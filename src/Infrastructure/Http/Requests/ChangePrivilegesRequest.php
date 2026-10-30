<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangePrivilegesRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:1', 'exists:users,id'],
            'add_roles' => ['sometimes', 'array'],
            'remove_roles' => ['sometimes', 'array'],
            'add_roles.*' => ['string', 'max:32', Rule::exists('roles', 'name')],
            'remove_roles.*' => ['string', 'max:32', Rule::exists('roles', 'name')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'add_roles.*.exists' => 'El rol seleccionado no existe',
            'remove_roles.*.exists' => 'El rol seleccionado no existe',
        ];
    }
}
