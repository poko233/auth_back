<?php

declare(strict_types=1);

use Lab\Domain\Access\Exception\RoleNotFound;
use Lab\Domain\Access\Permission;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Access\Role;
use Lab\Domain\Identity\UserId;
use Tests\Support\InMemoryRoleRepository;

/**
 * Tests de dominio de Access (sin Laravel, sin BD).
 */
it('VIEWER solo permite READ', function (): void {
    $role = Role::create('VIEWER', null, true, [Permission::create(PermissionName::fromString('READ'))]);

    expect($role->allows(PermissionName::fromString('READ')))->toBeTrue()
        ->and($role->allows(PermissionName::fromString('WRITE')))->toBeFalse()
        ->and($role->allows(PermissionName::fromString('DELETE')))->toBeFalse()
        ->and($role->allows(PermissionName::fromString('ADMIN')))->toBeFalse();
});

it('ADMIN permite todo', function (): void {
    $repo = InMemoryRoleRepository::withLabRoles();
    $role = $repo->findByName('ADMIN');

    foreach (['READ', 'WRITE', 'DELETE', 'ADMIN'] as $name) {
        expect($role->allows(PermissionName::fromString($name)))->toBeTrue();
    }
});

it('PermissionName rechaza formatos inválidos', function (): void {
    expect(fn () => PermissionName::fromString('read'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => PermissionName::fromString('READD ME'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => PermissionName::fromString('X'))->toThrow(InvalidArgumentException::class);
});

it('el puerto asigna y revoca roles por nombre', function (): void {
    $repo = InMemoryRoleRepository::withLabRoles();
    $userId = UserId::fromString('1');

    expect($repo->currentRolesOf($userId))->toBe([]);

    $repo->assignToUser($userId, 'VIEWER');
    $repo->assignToUser($userId, 'VIEWER');

    $roles = $repo->currentRolesOf($userId);

    expect($roles)->toHaveCount(1)
        ->and($roles[0]->allows(PermissionName::fromString('READ')))->toBeTrue();

    $repo->revokeFromUser($userId, 'VIEWER');

    expect($repo->currentRolesOf($userId))->toBe([]);
});

it('asignar un rol desconocido lanza RoleNotFound', function (): void {
    $repo = InMemoryRoleRepository::withLabRoles();

    expect(fn () => $repo->assignToUser(UserId::fromString('2'), 'SUPER_ADMIN'))
        ->toThrow(RoleNotFound::class);
});

it('findByName devuelve el rol con permisos y null si no existe', function (): void {
    $repo = InMemoryRoleRepository::withLabRoles();

    expect($repo->findByName('EDITOR'))->not->toBeNull()
        ->and($repo->findByName('EDITOR')->allows(PermissionName::fromString('WRITE')))->toBeTrue()
        ->and($repo->findByName('GUEST'))->toBeNull()
        ->and($repo->all())->toHaveCount(3);
});

it('syncPermissions reemplaza los permisos del rol', function (): void {
    $repo = InMemoryRoleRepository::withLabRoles();
    $repo->syncPermissions('VIEWER', [PermissionName::fromString('READ'), PermissionName::fromString('WRITE')]);

    expect($repo->findByName('VIEWER')->allows(PermissionName::fromString('WRITE')))->toBeTrue();
});
