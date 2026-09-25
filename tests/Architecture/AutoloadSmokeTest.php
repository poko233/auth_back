<?php

declare(strict_types=1);

use Lab\Domain\Identity\UserId;

/**
 * Prueba de humo: verifica que el autoload de Composer resuelve
 * correctamente el namespace Lab\ desde src/.
 */
it('resuelve una clase del namespace Lab\\ vía autoload PSR-4', function (): void {
    $id = UserId::fromString('user-001');

    expect($id->value())->toBe('user-001');
});

it('UserId rechaza valores vacíos', function (): void {
    expect(fn () => UserId::fromString('   '))
        ->toThrow(InvalidArgumentException::class);
});

it('UserId compara por valor', function (): void {
    $a = UserId::fromString('user-001');
    $b = UserId::fromString('user-001');
    $c = UserId::fromString('user-002');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});