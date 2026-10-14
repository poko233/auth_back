<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\OAuth;

/**
 * Puerto de refresh tokens.
 */
interface RefreshTokenRepository
{
    public function save(RefreshToken $token): RefreshToken;

    public function findByHash(string $tokenHash): ?RefreshToken;
}
