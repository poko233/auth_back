<?php

declare(strict_types=1);

namespace Lab\Domain\Access\Exception;

use RuntimeException;

/**
 * Excepción de dominio: el rol solicitado no existe.
 */
final class RoleNotFound extends RuntimeException
{
}
