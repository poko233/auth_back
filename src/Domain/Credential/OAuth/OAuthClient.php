<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\OAuth;

/**
 * Cliente OAuth registrado (ROPC: valida client_id + client_secret).
 */
final class OAuthClient
{
    private function __construct(
        private ?int $id,
        private string $clientId,
        private string $clientSecretHash,
        private string $name,
    ) {
    }

    public static function create(string $clientId, string $clientSecretHash, string $name): self
    {
        return new self(null, $clientId, $clientSecretHash, $name);
    }

    public static function reconstitute(int $id, string $clientId, string $clientSecretHash, string $name): self
    {
        return new self($id, $clientId, $clientSecretHash, $name);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    public function clientSecretHash(): string
    {
        return $this->clientSecretHash;
    }

    public function name(): string
    {
        return $this->name;
    }
}
