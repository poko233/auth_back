<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing\Exception;

use RuntimeException;

/**
 * Excepción de dominio: el ticket solicitado no existe.
 */
final class TicketNotFound extends RuntimeException
{
}
