<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Lab\Domain\Ticketing\TicketStatus;

/**
 * Validación para cambiar el estado de un ticket.
 */
final class ChangeTicketStatusRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_column(TicketStatus::cases(), 'value'))],
        ];
    }
}
