<?php

declare(strict_types=1);

namespace Lab\Domain\Shared;

use DateTimeImmutable;

/**
 * Puerto (interface) del Domain para obtener la hora actual.
 *
 * Razón de ser: el experimento requiere medir expiración de credenciales
 * (API Key, JWT, OAuth access tokens). Sin un reloj controlable, los tests
 * de expiración dependen del reloj del sistema y se vuelven frágiles.
 *
 * El Domain define la interfaz; Infrastructure provee la implementación.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}