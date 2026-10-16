<?php

declare(strict_types=1);

use Lab\Domain\Credential\ApiKey\ApiKey;
use Lab\Domain\Credential\ApiKey\ApiKeyId;

/**
 * Tests de la credencial API Key (sin Laravel, sin BD).
 */
it('crea una key activa sin expiración', function (): void {
    $key = ApiKey::create(7, str_repeat('a', 64), 'CI');

    expect($key->id())->toBeNull()
        ->and($key->userId())->toBe(7)
        ->and($key->isRevoked())->toBeFalse()
        ->and($key->isExpired(new DateTimeImmutable))->toBeFalse();
});

it('detecta expiración contra el reloj', function (): void {
    $now = new DateTimeImmutable;
    $key = ApiKey::create(7, str_repeat('b', 64), null, $now->modify('-1 minute'));

    expect($key->isExpired($now))->toBeTrue();
});

it('revoca marcando revoked_at', function (): void {
    $now = new DateTimeImmutable;
    $key = ApiKey::create(7, str_repeat('c', 64));

    $key->revoke($now);

    expect($key->isRevoked())->toBeTrue()
        ->and($key->revokedAt())->toBe($now);
});

it('ApiKeyId rechaza cero y negativos', function (): void {
    expect(fn () => ApiKeyId::fromInt(0))->toThrow(InvalidArgumentException::class)
        ->and(fn () => ApiKeyId::fromInt(-2))->toThrow(InvalidArgumentException::class);
});

it('conserva el primer instante de revocación', function (): void {
    $key = ApiKey::create(7, str_repeat('d', 64), 'Prueba');
    $first = new DateTimeImmutable('2026-01-01T12:00:00Z');
    $key->revoke($first);
    $key->revoke($first->modify('+1 hour'));

    expect($key->revokedAt())->toBe($first);
});
