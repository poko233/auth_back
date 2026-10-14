<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\ApiKey;

use DateTimeImmutable;
use Lab\Domain\Credential\ApiKey\ApiKey;
use Lab\Domain\Credential\ApiKey\ApiKeyGenerator;
use Lab\Domain\Credential\ApiKey\ApiKeyRepository;
use Lab\Domain\Identity\Exception\UserNotFound;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Identity\UserRepository;

/**
 * Emite una API Key: genera el plano, persiste solo el hash.
 *
 * Devuelve el plano una sola vez (nunca se puede recuperar después).
 */
final class IssueApiKey
{
    public function __construct(
        private ApiKeyRepository $apiKeys,
        private UserRepository $users,
        private ApiKeyGenerator $generator,
    ) {
    }

    /**
     * @return array{apiKey: ApiKey, plain: string}
     */
    public function execute(int $userId, ?string $label = null, ?DateTimeImmutable $expiresAt = null): array
    {
        if ($this->users->findById(UserId::fromString((string) $userId)) === null) {
            throw new UserNotFound("Usuario no encontrado: {$userId}.");
        }

        $plain = $this->generator->generate();
        $apiKey = $this->apiKeys->save(ApiKey::create($userId, AuthenticateWithApiKey::hash($plain), $label, $expiresAt));

        return ['apiKey' => $apiKey, 'plain' => $plain];
    }
}
