<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\Jwt;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Claims del JWT del laboratorio (RFC 7519, subset).
 */
final readonly class JwtClaims
{
    public function __construct(
        public int $sub,
        public string $jti,
        public DateTimeImmutable $iat,
        public DateTimeImmutable $exp,
    ) {
        if ($sub <= 0) {
            throw new InvalidArgumentException('sub debe ser un id de usuario positivo.');
        }

        if (trim($jti) === '') {
            throw new InvalidArgumentException('jti no puede estar vacío.');
        }
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now >= $this->exp;
    }
}
