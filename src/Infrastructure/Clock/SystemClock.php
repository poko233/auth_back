<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Clock;

use DateTimeImmutable;
use Lab\Domain\Shared\Clock;

/**
 * Implementación por defecto del puerto Clock.
 *
 * Detalle técnico sustituible: los tests inyectarán un FakeClock que
 * permite avanzar el tiempo sin esperar realmente, imprescindible para
 * las pruebas de expiración de credenciales del experimento.
 */
final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}