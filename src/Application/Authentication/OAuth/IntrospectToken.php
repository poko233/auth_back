<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\OAuth;

use Lab\Domain\Credential\OAuth\AccessTokenRepository;
use Lab\Domain\Shared\Clock;

/**
 * Introspección RFC 7662 simplificada: estado de un access token.
 */
final class IntrospectToken
{
    public function __construct(
        private AccessTokenRepository $accessTokens,
        private Clock $clock,
    ) {
    }

    /**
     * @return array{active: bool, user_id?: int, scopes?: list<string>, exp?: string}
     */
    public function execute(string $plainToken): array
    {
        $token = $this->accessTokens->findByHash(IssueTokenByPassword::hash($plainToken));

        if ($token === null) {
            return ['active' => false];
        }

        $now = $this->clock->now();

        if ($token->isRevoked() || $token->isExpired($now)) {
            return ['active' => false];
        }

        return [
            'active' => true,
            'user_id' => $token->userId(),
            'scopes' => $token->scopes(),
            'exp' => $token->expiresAt()->format(DATE_ATOM),
        ];
    }
}
