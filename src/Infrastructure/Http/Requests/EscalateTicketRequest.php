<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para escalar un ticket.
 */
final class EscalateTicketRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to_role' => ['required', 'string', 'max:32'],
            'note' => ['nullable', 'string'],
        ];
    }
}
