<?php

declare(strict_types=1);

namespace Lab\Domain\Access\Exception;

use RuntimeException;

/**
 * Excepción de dominio: los permisos de sistema no se borran ni renombran.
 */
final class SystemPermissionProtected extends RuntimeException
{
}
