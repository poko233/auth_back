<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\ApiKey\Exception;

use RuntimeException;

/**
 * Excepción de dominio: la API Key fue revocada.
 */
final class ApiKeyRevoked extends RuntimeException
{
}
