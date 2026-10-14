<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para asignar un ticket (a usuario o a rol, excluyentes).
 */
final class AssignTicketRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required_without:role', 'integer', 'min:1'],
            'role' => ['required_without:user_id', 'string', 'max:32'],
        ];
    }
}
