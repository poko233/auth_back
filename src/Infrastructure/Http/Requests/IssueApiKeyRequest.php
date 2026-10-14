<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para emitir una API Key.
 */
final class IssueApiKeyRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:1'],
            'label' => ['nullable', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
