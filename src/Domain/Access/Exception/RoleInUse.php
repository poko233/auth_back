<?php

declare(strict_types=1);

namespace Lab\Domain\Access\Exception;

use RuntimeException;

/**
 * Excepción de dominio: no se puede borrar un rol asignado a usuarios.
 */
final class RoleInUse extends RuntimeException
{
}
