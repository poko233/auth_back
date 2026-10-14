<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\ApiKey;

use DateTimeImmutable;

/**
 * Credencial API Key de larga vida.
 *
 * Solo viaja el hash (sha256 del plano): el plano se entrega una sola vez al
 * emitir y nunca se persiste. Expiración y revocación las evalúa
 * AuthenticateWithApiKey.
 */
final class ApiKey
{
    private function __construct(
        private ?ApiKeyId $id,
        private int $userId,
        private string $keyHash,
        private ?string $label,
        private ?DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $revokedAt,
    ) {
    }

    public static function create(int $userId, string $keyHash, ?string $label = null, ?DateTimeImmutable $expiresAt = null): self
    {
        return new self(null, $userId, $keyHash, $label, $expiresAt, null);
    }

    public static function reconstitute(
        ApiKeyId $id,
        int $userId,
        string $keyHash,
        ?string $label,
        ?DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $revokedAt,
    ): self {
        return new self($id, $userId, $keyHash, $label, $expiresAt, $revokedAt);
    }

    public function id(): ?ApiKeyId
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function keyHash(): string
    {
        return $this->keyHash;
    }

    public function label(): ?string
    {
        return $this->label;
    }

    public function expiresAt(): ?DateTimeImmutable
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
        return $this->expiresAt !== null && $now >= $this->expiresAt;
    }

    public function revoke(DateTimeImmutable $now): void
    {
        $this->revokedAt = $now;
    }
}
