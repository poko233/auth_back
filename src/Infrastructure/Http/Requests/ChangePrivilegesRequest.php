<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Requests;

use Illuminate\Validation\Rule;

// en rules():
'add_roles.*' => ['string', 'max:32', Rule::exists('roles', 'name')],
'remove_roles.*' => ['string', 'max:32', Rule::exists('roles', 'name')],

// nuevo método:
/** @return array<string, string> */
public function messages(): array
{
    return [
        'add_roles.*.exists' => 'El rol seleccionado no existe',
        'remove_roles.*.exists' => 'El rol seleccionado no existe',
    ];
}