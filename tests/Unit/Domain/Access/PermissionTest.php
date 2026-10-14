<?php

declare(strict_types=1);

use Lab\Domain\Access\Exception\SystemPermissionProtected;
use Lab\Domain\Access\Permission;
use Lab\Domain\Access\PermissionName;

/**
 * Tests de la entidad Permission (sin Laravel, sin BD).
 */
it('crea un permiso libre sin sistema', function (): void {
    $permission = Permission::create(PermissionName::fromString('EXPORT'), 'Exportar tickets');

    expect($permission->id())->toBeNull()
        ->and($permission->name()->value())->toBe('EXPORT')
        ->and($permission->isSystem())->toBeFalse();
});

it('reconstituye un permiso persistido', function (): void {
    $permission = Permission::reconstitute(5, PermissionName::fromString('READ'), null, true);

    expect($permission->id())->toBe(5)
        ->and($permission->isSystem())->toBeTrue();
});

it('renombra un permiso libre', function (): void {
    $permission = Permission::create(PermissionName::fromString('EXPORT_OLD'));
    $permission->rename(PermissionName::fromString('EXPORT_NEW'), 'Nueva descripción');

    expect($permission->name()->value())->toBe('EXPORT_NEW');
});

it('protege a los permisos de sistema contra renombre', function (): void {
    $permission = Permission::reconstitute(1, PermissionName::fromString('READ'), null, true);

    expect(fn () => $permission->rename(PermissionName::fromString('READ_X')))
        ->toThrow(SystemPermissionProtected::class);
});
