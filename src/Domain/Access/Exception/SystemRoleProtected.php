<?php

declare(strict_types=1);

namespace Lab\Domain\Access\Exception;

use RuntimeException;

/**
 * Excepción de dominio: los roles de sistema no se borran.
 */
final class SystemRoleProtected extends RuntimeException
{
}
