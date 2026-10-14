<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\ApiKey;

use Lab\Domain\Credential\ApiKey\ApiKey;
use Lab\Domain\Credential\ApiKey\ApiKeyRepository;

/**
 * Lista las API Keys de un usuario (sin exponer hashes planos: no existen).
 */
final class ListApiKeys
{
    public function __construct(
        private ApiKeyRepository $apiKeys,
    ) {
    }

    /**
     * @return list<ApiKey>
     */
    public function execute(int $userId): array
    {
        return $this->apiKeys->listByUser($userId);
    }
}
