<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\OAuth;

use DateTimeImmutable;
use Lab\Application\Authentication\Basic\AuthenticateWithBasic;
use Lab\Domain\Credential\ApiKey\ApiKeyGenerator;
use Lab\Domain\Credential\OAuth\AccessToken;
use Lab\Domain\Credential\OAuth\AccessTokenRepository;
use Lab\Domain\Credential\OAuth\Exception\InvalidGrant;
use Lab\Domain\Credential\OAuth\OAuthClientRepository;
use Lab\Domain\Credential\OAuth\RefreshToken;
use Lab\Domain\Credential\OAuth\RefreshTokenRepository;
use Lab\Domain\Identity\PasswordHasher;
use Lab\Domain\Shared\Clock;

/**
 * Grant ROPC: client_id+secret y usuario+password → par de tokens.
 *
 * Reutiliza AuthenticateWithBasic para el resource owner: mismo input que
 * Basic/API Key, distinto mecanismo. Comparación limpia para el experimento.
 */
final class IssueTokenByPassword
{
    public function __construct(
        private OAuthClientRepository $clients,
        private AccessTokenRepository $accessTokens,
        private RefreshTokenRepository $refreshTokens,
        private AuthenticateWithBasic $basic,
        private PasswordHasher $hasher,
        private ApiKeyGenerator $generator,
        private Clock $clock,
    ) {
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * @param list<string> $scopes
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function execute(string $clientId, string $clientSecret, string $email, string $password, array $scopes = [], int $ttlSeconds = 3600): array
    {
        $client = $this->clients->findByClientId($clientId);

        if ($client === null || ! $this->hasher->verify($clientSecret, $client->clientSecretHash())) {
            throw new InvalidGrant('Cliente inválido.');
        }

        $principal = $this->basic->execute($email, $password);

        if ($principal === null) {
            throw new InvalidGrant('Credenciales de usuario inválidas.');
        }

        $now = $this->clock->now();
        $userId = (int) $principal->userId->value();
        $accessPlain = $this->generator->generate();
        $refreshPlain = $this->generator->generate();

        $access = $this->accessTokens->save(AccessToken::create(
            self::hash($accessPlain),
            $client->id(),
            $userId,
            $scopes,
            $now->modify("+{$ttlSeconds} seconds"),
        ));

        $this->refreshTokens->save(RefreshToken::create(
            self::hash($refreshPlain),
            $access->id(),
            $now->modify('+30 days'),
        ));

        return ['access_token' => $accessPlain, 'refresh_token' => $refreshPlain, 'expires_in' => $ttlSeconds];
    }
}
