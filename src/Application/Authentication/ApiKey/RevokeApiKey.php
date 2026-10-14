<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\ApiKey;

use Lab\Domain\Credential\ApiKey\ApiKeyId;
use Lab\Domain\Credential\ApiKey\ApiKeyRepository;
use Lab\Domain\Credential\ApiKey\Exception\ApiKeyNotFound;
use Lab\Domain\Shared\Clock;

/**
 * Revoca una API Key (marca revoked_at, conserva la fila para auditoría).
 */
final class RevokeApiKey
{
    public function __construct(
        private ApiKeyRepository $apiKeys,
        private Clock $clock,
    ) {
    }

    public function execute(ApiKeyId $id): void
    {
        $apiKey = $this->apiKeys->findById($id);

        if ($apiKey === null) {
            throw new ApiKeyNotFound("API Key no encontrada: {$id->value()}.");
        }

        $apiKey->revoke($this->clock->now());
        $this->apiKeys->save($apiKey);
    }
}
