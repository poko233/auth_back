<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Security\Jwt;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Lab\Domain\Credential\Jwt\Exception\JwtInvalid;
use Lab\Domain\Credential\Jwt\JwtClaims;
use Lab\Domain\Credential\Jwt\JwtSigner;
use Throwable;

/**
 * Firma HS256 con firebase/php-jwt como primitiva criptográfica.
 *
 * El flujo (emisión, vigencia, revocación) vive en Application; aquí solo
 * criptografía: firmar, verificar firma+vigencia, parsear firma sin vigencia.
 */
final class FirebaseJwtSigner implements JwtSigner
{
    public function __construct(
        private string $secret,
        private string $algo = 'HS256',
    ) {
    }

    public function sign(int $sub, string $jti, int $ttlSeconds): string
    {
        $now = time();

        return JWT::encode([
            'sub' => $sub,
            'jti' => $jti,
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ], $this->secret, $this->algo);
    }

    public function verify(string $token): JwtClaims
    {
        return $this->decode($token, true);
    }

    public function parse(string $token): JwtClaims
    {
        return $this->decode($token, false);
    }

    private function decode(string $token, bool $checkExpiry): JwtClaims
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));
        } catch (Throwable $e) {
            throw new JwtInvalid('Token inválido: '.$e->getMessage(), 0, $e);
        }

        if (! isset($decoded->sub, $decoded->jti, $decoded->iat, $decoded->exp)) {
            throw new JwtInvalid('Token sin claims requeridos.');
        }

        $claims = new JwtClaims(
            (int) $decoded->sub,
            (string) $decoded->jti,
            new DateTimeImmutable('@'.((int) $decoded->iat)),
            new DateTimeImmutable('@'.((int) $decoded->exp)),
        );

        if ($checkExpiry && $claims->isExpired(new DateTimeImmutable())) {
            throw new JwtInvalid('Token expirado.');
        }

        return $claims;
    }
}
