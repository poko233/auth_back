<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\OAuth;

use Lab\Domain\Credential\OAuth\AccessTokenRepository;
use Lab\Domain\Credential\OAuth\Exception\InvalidGrant;
use Lab\Domain\Shared\Clock;

/**
 * Revoca un access token (marca revoked_at; la fila persiste).
 */
final class RevokeToken
{
    public function __construct(
        private AccessTokenRepository $accessTokens,
        private Clock $clock,
    ) {
    }

    public function execute(string $plainToken): void
    {
        $token = $this->accessTokens->findByHash(IssueTokenByPassword::hash($plainToken));

        if ($token === null) {
            throw new InvalidGrant('Token desconocido.');
        }

        $token->revoke($this->clock->now());
        $this->accessTokens->save($token);
    }
}
