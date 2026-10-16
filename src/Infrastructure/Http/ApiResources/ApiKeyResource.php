<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\ApiResources;

use Lab\Domain\Credential\ApiKey\ApiKey;

/**
 * Representación JSON de una API Key (nunca el hash ni el plano).
 */
final class ApiKeyResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(ApiKey $apiKey): array
    {
        return [
            'id' => $apiKey->id()?->value(),
            'user_id' => $apiKey->userId(),
            'label' => $apiKey->label(),
            'created_at' => $apiKey->createdAt()?->format(DATE_ATOM),
            'expires_at' => $apiKey->expiresAt()?->format(DATE_ATOM),
            'revoked_at' => $apiKey->revokedAt()?->format(DATE_ATOM),
        ];
    }
}
