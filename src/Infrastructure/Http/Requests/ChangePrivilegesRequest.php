<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para cambios de privilegios del escenario.
 */
final class ChangePrivilegesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:1'],
            'add_roles' => ['sometimes', 'array'],
            'add_roles.*' => ['string', 'max:32'],
            'remove_roles' => ['sometimes', 'array'],
            'remove_roles.*' => ['string', 'max:32'],
        ];
    }
}
