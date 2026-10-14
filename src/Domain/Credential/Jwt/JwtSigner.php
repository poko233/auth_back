<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\Jwt;

use Lab\Domain\Credential\Jwt\Exception\JwtInvalid;

/**
 * Puerto de firma/verificación JWT.
 *
 * firebase/php-jwt es solo primitiva criptográfica: el flujo (emisión,
 * vigencia, revocación) vive en Application. La firma verifica autenticidad;
 * parse() omite expiración para poder revocar tokens ya vencidos.
 */
interface JwtSigner
{
    public function sign(int $sub, string $jti, int $ttlSeconds): string;

    /**
     * @throws JwtInvalid
     */
    public function verify(string $token): JwtClaims;

    /**
     * Verifica firma sin exigir vigencia.
     *
     * @throws JwtInvalid
     */
    public function parse(string $token): JwtClaims;
}
