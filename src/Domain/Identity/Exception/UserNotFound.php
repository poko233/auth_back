<?php

declare(strict_types=1);

namespace Lab\Domain\Identity\Exception;

use RuntimeException;

/**
 * Excepción de dominio: el usuario solicitado no existe.
 */
final class UserNotFound extends RuntimeException
{
}
