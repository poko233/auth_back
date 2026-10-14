<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\OAuth;

use DateTimeImmutable;

/**
 * Access token OAuth: string aleatorio, hash en BD, expiración corta.
 */
final class AccessToken
{
    /**
     * @param list<string> $scopes
     */
    private function __construct(
        private ?int $id,
        private string $tokenHash,
        private ?int $clientId,
        private int $userId,
        private array $scopes,
        private DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $revokedAt,
    ) {
    }

    /**
     * @param list<string> $scopes
     */
    public static function create(string $tokenHash, ?int $clientId, int $userId, array $scopes, DateTimeImmutable $expiresAt): self
    {
        return new self(null, $tokenHash, $clientId, $userId, array_values($scopes), $expiresAt, null);
    }

    /**
     * @param list<string> $scopes
     */
    public static function reconstitute(int $id, string $tokenHash, ?int $clientId, int $userId, array $scopes, DateTimeImmutable $expiresAt, ?DateTimeImmutable $revokedAt): self
    {
        return new self($id, $tokenHash, $clientId, $userId, array_values($scopes), $expiresAt, $revokedAt);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function clientId(): ?int
    {
        return $this->clientId;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }

    public function revoke(DateTimeImmutable $now): void
    {
        $this->revokedAt = $now;
    }
}
