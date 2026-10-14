<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Security\ApiKey;

use Lab\Domain\Credential\ApiKey\ApiKeyGenerator;
use Random\RandomException;

/**
 * Generador de keys planas con alta entropía (256 bits en hex).
 */
final class SecureApiKeyGenerator implements ApiKeyGenerator
{
    public function generate(): string
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (RandomException $e) {
            throw new \RuntimeException('No se pudo generar la API Key.', 0, $e);
        }
    }
}
