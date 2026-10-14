<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\OAuth;

use Lab\Domain\Credential\ApiKey\ApiKeyGenerator;
use Lab\Domain\Credential\OAuth\AccessToken;
use Lab\Domain\Credential\OAuth\AccessTokenRepository;
use Lab\Domain\Credential\OAuth\Exception\InvalidGrant;
use Lab\Domain\Credential\OAuth\RefreshToken;
use Lab\Domain\Credential\OAuth\RefreshTokenRepository;
use Lab\Domain\Shared\Clock;

/**
 * Rota un refresh token: emite par nuevo y revoca el anterior.
 *
 * Reuso-detection como JWT: presentar un refresh revocado revoca la familia.
 */
final class RefreshAccessToken
{
    public function __construct(
        private AccessTokenRepository $accessTokens,
        private RefreshTokenRepository $refreshTokens,
        private ApiKeyGenerator $generator,
        private Clock $clock,
    ) {
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function execute(string $refreshPlain, int $ttlSeconds = 3600): array
    {
        $refresh = $this->refreshTokens->findByHash(IssueTokenByPassword::hash($refreshPlain));

        if ($refresh === null) {
            throw new InvalidGrant('Refresh token desconocido.');
        }

        $now = $this->clock->now();

        if ($refresh->isRevoked()) {
            $this->revokeFamily($refresh);
            throw new InvalidGrant('Refresh token ya usado: familia revocada por reuso.');
        }

        if ($refresh->isExpired($now)) {
            throw new InvalidGrant('Refresh token expirado.');
        }

        $accessPlain = $this->generator->generate();
        $newRefreshPlain = $this->generator->generate();

        $parent = $this->accessTokens->findById($refresh->accessTokenId());

        if ($parent === null) {
            throw new InvalidGrant('Access token padre inexistente.');
        }

        $access = $this->accessTokens->save(AccessToken::create(
            IssueTokenByPassword::hash($accessPlain),
            $parent->clientId(),
            $parent->userId(),
            $parent->scopes(),
            $now->modify("+{$ttlSeconds} seconds"),
        ));

        $newRefresh = RefreshToken::create(
            IssueTokenByPassword::hash($newRefreshPlain),
            $access->id(),
            $now->modify('+30 days'),
        );
        $this->refreshTokens->save($newRefresh);

        $refresh->markReplaced(IssueTokenByPassword::hash($newRefreshPlain), $now);
        $this->refreshTokens->save($refresh);

        return ['access_token' => $accessPlain, 'refresh_token' => $newRefreshPlain, 'expires_in' => $ttlSeconds];
    }

    private function revokeFamily(RefreshToken $refresh): void
    {
        $now = $this->clock->now();
        $current = $refresh;
        $guard = 0;

        while ($current->replacedBy() !== null && $guard < 100) {
            $child = $this->refreshTokens->findByHash($current->replacedBy());

            if ($child === null) {
                break;
            }

            $child->revoke($now);
            $this->refreshTokens->save($child);

            // El access token hijo seguiría vivo: también se revoca.
            $childAccess = $this->accessTokens->findById($child->accessTokenId());

            if ($childAccess !== null) {
                $childAccess->revoke($now);
                $this->accessTokens->save($childAccess);
            }

            $current = $child;
            $guard++;
        }
    }
}
