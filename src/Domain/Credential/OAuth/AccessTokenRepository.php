<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\OAuth;

/**
 * Puerto de access tokens.
 */
interface AccessTokenRepository
{
    public function save(AccessToken $token): AccessToken;

    public function findById(int $id): ?AccessToken;

    public function findByHash(string $tokenHash): ?AccessToken;

    /**
     * @return list<AccessToken>
     */
    public function listByUser(int $userId): array;
}
