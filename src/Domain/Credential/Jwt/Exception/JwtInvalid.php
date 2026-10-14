<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\Jwt\Exception;

use RuntimeException;

/**
 * Excepción de dominio: token malformado, firma inválida o expirado.
 */
final class JwtInvalid extends RuntimeException
{
}
