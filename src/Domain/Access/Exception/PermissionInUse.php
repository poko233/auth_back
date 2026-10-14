<?php

declare(strict_types=1);

namespace Lab\Domain\Access\Exception;

use RuntimeException;

/**
 * Excepción de dominio: no se puede borrar un permiso asignado a roles.
 */
final class PermissionInUse extends RuntimeException
{
}
