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
            'label' => ['required', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'label.required' => 'La etiqueta es obligatoria',
            'label.max' => 'La etiqueta no puede superar los 100 caracteres',
            'expires_at.date' => 'La fecha de expiración no es válida',
            'expires_at.after' => 'La fecha de expiración debe ser posterior a la fecha actual',
        ];
    }
}
