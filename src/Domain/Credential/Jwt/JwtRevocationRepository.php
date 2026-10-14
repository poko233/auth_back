<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\Jwt;

use Lab\Domain\Credential\Jwt\Exception\JwtInvalid;

/**
 * Puerto de denylist de JTIs con cadena de rotación.
 */
interface JwtRevocationRepository
{
    public function revoke(string $jti): void;

    public function isRevoked(string $jti): bool;

    /**
     * Registra que oldJti fue rotado a newJti (para reuso-detection).
     */
    public function recordReplacement(string $oldJti, string $newJti): void;

    /**
     * Devuelve el jti hijo si oldJti fue rotado, null si no.
     */
    public function findReplacement(string $oldJti): ?string;
}
