<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\ApiKey;

/**
 * Puerto de persistencia de API Keys (búsqueda por hash para el login).
 */
interface ApiKeyRepository
{
    public function save(ApiKey $apiKey): ApiKey;

    public function findById(ApiKeyId $id): ?ApiKey;

    public function findByHash(string $keyHash): ?ApiKey;

    /**
     * @return list<ApiKey>
     */
    public function listByUser(int $userId): array;

    public function delete(ApiKeyId $id): void;
}
