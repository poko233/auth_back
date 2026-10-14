<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\ApiKey;

use Lab\Application\Authentication\AuthenticatedPrincipal;
use Lab\Domain\Credential\ApiKey\ApiKeyRepository;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Shared\Clock;

/**
 * Autentica un key plano: hash → búsqueda → vigencia → principal.
 *
 * Devuelve null si el key es desconocido (el middleware responde 401).
 * Expiración y revocación se evalúan aquí, contra el reloj inyectado.
 * Coste: exactamente 1 query (findByHash), igual que los demás mecanismos.
 */
final class AuthenticateWithApiKey
{
    public function __construct(
        private ApiKeyRepository $apiKeys,
        private Clock $clock,
    ) {
    }

    public static function hash(string $plainKey): string
    {
        return hash('sha256', $plainKey);
    }

    public function execute(string $plainKey): ?AuthenticatedPrincipal
    {
        if (trim($plainKey) === '') {
            return null;
        }

        $apiKey = $this->apiKeys->findByHash(self::hash($plainKey));

        if ($apiKey === null) {
            return null;
        }

        $now = $this->clock->now();

        if ($apiKey->isRevoked() || $apiKey->isExpired($now)) {
            return null;
        }

        return new AuthenticatedPrincipal(
            UserId::fromString((string) $apiKey->userId()),
            'api_key',
            ['key_id' => $apiKey->id()?->value(), 'label' => $apiKey->label()],
        );
    }
}
