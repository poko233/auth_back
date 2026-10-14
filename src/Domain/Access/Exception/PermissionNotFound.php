<?php

declare(strict_types=1);

namespace Lab\Domain\Access\Exception;

use RuntimeException;

/**
 * Excepción de dominio: el permiso solicitado no existe.
 */
final class PermissionNotFound extends RuntimeException
{
}
