<?php

declare(strict_types=1);

use Lab\Domain\Credential\Jwt\JwtClaims;

/**
 * Tests de los claims JWT (sin Laravel, sin BD).
 */
it('detecta expiración contra el reloj', function (): void {
    $now = new DateTimeImmutable();
    $claims = new JwtClaims(7, 'jti-1', $now->modify('-2 hours'), $now->modify('-1 hour'));

    expect($claims->isExpired($now))->toBeTrue();
});

it('rechaza sub inválido y jti vacío', function (): void {
    $now = new DateTimeImmutable();

    expect(fn () => new JwtClaims(0, 'jti', $now, $now->modify('+1 hour')))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => new JwtClaims(1, '  ', $now, $now->modify('+1 hour')))
        ->toThrow(InvalidArgumentException::class);
});
