<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\OAuth;

use DateTimeImmutable;

/**
 * Refresh token OAuth con cadena de rotación (reuso-detection como JWT).
 */
final class RefreshToken
{
    private function __construct(
        private ?int $id,
        private string $tokenHash,
        private int $accessTokenId,
        private DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $revokedAt,
        private ?string $replacedBy,
    ) {
    }

    public static function create(string $tokenHash, int $accessTokenId, DateTimeImmutable $expiresAt): self
    {
        return new self(null, $tokenHash, $accessTokenId, $expiresAt, null, null);
    }

    public static function reconstitute(int $id, string $tokenHash, int $accessTokenId, DateTimeImmutable $expiresAt, ?DateTimeImmutable $revokedAt, ?string $replacedBy): self
    {
        return new self($id, $tokenHash, $accessTokenId, $expiresAt, $revokedAt, $replacedBy);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function accessTokenId(): int
    {
        return $this->accessTokenId;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function replacedBy(): ?string
    {
        return $this->replacedBy;
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

    public function markReplaced(string $newHash, DateTimeImmutable $now): void
    {
        $this->revokedAt = $now;
        $this->replacedBy = $newHash;
    }
}
