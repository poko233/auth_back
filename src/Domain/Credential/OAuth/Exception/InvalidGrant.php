<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\OAuth\Exception;

use RuntimeException;

/**
 * Excepción de dominio: grant inválido (cliente o credenciales malas).
 */
final class InvalidGrant extends RuntimeException
{
}
