<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\OAuth;

use Lab\Domain\Credential\OAuth\AccessTokenRepository;
use Lab\Domain\Credential\OAuth\Exception\InvalidGrant;
use Lab\Domain\Credential\OAuth\Exception\TokenAlreadyRevoked;
use Lab\Domain\Credential\OAuth\RefreshTokenRepository;
use Lab\Domain\Shared\Clock;
use Lab\Domain\Shared\TransactionManager;

/**
 * Revoca un access token (marca revoked_at; la fila persiste).
 */
final class RevokeToken
{
    public function __construct(
        private AccessTokenRepository $accessTokens,
        private Clock $clock,
        private RefreshTokenRepository $refreshTokens,
        private TransactionManager $transactions,
    ) {
    }

    public function execute(string $plainToken): void
    {
        $alreadyRevoked = $this->transactions->transaction(function () use ($plainToken): bool {
            $token = $this->accessTokens->findByHash(IssueTokenByPassword::hash($plainToken));

            if ($token === null) {
                throw new InvalidGrant('Token desconocido.');
            }

            $alreadyRevoked = $token->isRevoked();
            $now = $this->clock->now();

            if (! $alreadyRevoked) {
                $token->revoke($now);
                $this->accessTokens->save($token);
            }

            $this->refreshTokens->revokeByAccessTokenId($token->id(), $now);

            return $alreadyRevoked;
        });

        // La reparación de refresh históricos debe persistir también al responder 409.
        if ($alreadyRevoked) {
            throw new TokenAlreadyRevoked('El token ya estaba revocado');
        }
    }
}
