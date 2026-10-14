<?php

declare(strict_types=1);

namespace Tests\Support;

use Lab\Domain\Access\Exception\RoleNotFound;
use Lab\Domain\Access\Permission;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Access\Role;
use Lab\Domain\Access\RoleRepository;
use Lab\Domain\Identity\UserId;

/**
 * Fake en memoria del puerto de roles, reutilizable en 5B y 5F.
 *
 * Clona al guardar y al devolver: el repo real devuelve instancias nuevas
 * en cada consulta.
 */
final class InMemoryRoleRepository implements RoleRepository
{
    /** @var array<string, Role> */
    private array $roles = [];

    /** @var array<string, list<string>> userId => roleNames */
    private array $userRoles = [];

    /**
     * @param list<Role> $roles Roles base del sistema.
     */
    public function __construct(array $roles = [])
    {
        foreach ($roles as $role) {
            $this->roles[$role->name()] = clone $role;
        }
    }

    public function currentRolesOf(UserId $userId): array
    {
        $names = $this->userRoles[$userId->value()] ?? [];
        $result = [];

        foreach ($names as $name) {
            if (isset($this->roles[$name])) {
                $result[] = clone $this->roles[$name];
            }
        }

        return $result;
    }

    public function all(): array
    {
        $result = [];

        foreach ($this->roles as $role) {
            $result[] = clone $role;
        }

        return $result;
    }

    public function findByName(string $name): ?Role
    {
        return isset($this->roles[$name]) ? clone $this->roles[$name] : null;
    }

    public function save(Role $role): void
    {
        $this->roles[$role->name()] = clone $role;
    }

    public function delete(string $roleName): void
    {
        unset($this->roles[$roleName]);
    }

    public function syncPermissions(string $roleName, array $permissions): void
    {
        if (! isset($this->roles[$roleName])) {
            throw new RoleNotFound("Rol desconocido: {$roleName}.");
        }

        $entities = [];

        foreach ($permissions as $name) {
            $entities[] = Permission::create($name);
        }

        $role = clone $this->roles[$roleName];
        $role->syncPermissions($entities);
        $this->roles[$roleName] = $role;
    }

    public function assignToUser(UserId $userId, string $roleName): void
    {
        if (! isset($this->roles[$roleName])) {
            throw new RoleNotFound("Rol desconocido: {$roleName}.");
        }

        $current = $this->userRoles[$userId->value()] ?? [];

        if (! in_array($roleName, $current, true)) {
            $current[] = $roleName;
        }

        $this->userRoles[$userId->value()] = $current;
    }

    public function revokeFromUser(UserId $userId, string $roleName): void
    {
        $current = $this->userRoles[$userId->value()] ?? [];
        $this->userRoles[$userId->value()] = array_values(
            array_filter($current, fn (string $name): bool => $name !== $roleName),
        );
    }

    /**
     * Construye el repo con los 3 roles base del laboratorio.
     */
    public static function withLabRoles(): self
    {
        $read = Permission::create(PermissionName::fromString('READ'));
        $write = Permission::create(PermissionName::fromString('WRITE'));
        $delete = Permission::create(PermissionName::fromString('DELETE'));
        $admin = Permission::create(PermissionName::fromString('ADMIN'));

        return new self([
            Role::create('VIEWER', null, true, [$read]),
            Role::create('EDITOR', null, true, [$read, $write]),
            Role::create('ADMIN', null, true, [$read, $write, $delete, $admin]),
        ]);
    }
}
