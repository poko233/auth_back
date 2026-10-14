<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del endpoint /token (grant password).
 */
final class OAuthTokenRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'grant_type' => ['required', 'string'],
            'client_id' => ['required', 'string', 'max:64'],
            'client_secret' => ['required', 'string'],
            'username' => ['required_if:grant_type,password', 'email', 'max:255'],
            'password' => ['required_if:grant_type,password', 'string'],
        ];
    }
}
