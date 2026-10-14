<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\ApiKey\Exception;

use RuntimeException;

/**
 * Excepción de dominio: la API Key expiró.
 */
final class ApiKeyExpired extends RuntimeException
{
}
