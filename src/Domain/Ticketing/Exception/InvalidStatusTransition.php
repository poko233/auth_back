<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing\Exception;

use RuntimeException;

/**
 * Excepción de dominio: transición de estado no permitida.
 */
final class InvalidStatusTransition extends RuntimeException
{
}
