<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\OAuth;

use Lab\Application\Authentication\AuthenticatedPrincipal;
use Lab\Domain\Credential\OAuth\AccessTokenRepository;
use Lab\Domain\Credential\OAuth\RefreshTokenRepository;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Shared\Clock;

/**
 * Verifica un Bearer OAuth: hash → vigencia → revocación → principal.
 *
 * Coste: exactamente 1 query (findByHash con toda la info), igual que JWT
 * (cripto + lookup) y que API Key/Basic. Sin cripto: marginalmente más
 * rápido en teoría, el informe lo confirmará o refutará.
 */
final class VerifyOAuth
{
    public function __construct(
        private AccessTokenRepository $accessTokens,
        private Clock $clock,
    ) {
    }

    public function execute(string $plainToken): ?AuthenticatedPrincipal
    {
        if (trim($plainToken) === '') {
            return null;
        }

        $token = $this->accessTokens->findByHash(IssueTokenByPassword::hash($plainToken));

        if ($token === null) {
            return null;
        }

        $now = $this->clock->now();

        if ($token->isRevoked() || $token->isExpired($now)) {
            return null;
        }

        return new AuthenticatedPrincipal(
            UserId::fromString((string) $token->userId()),
            'oauth',
            ['token_id' => $token->id(), 'scopes' => $token->scopes()],
        );
    }
}
