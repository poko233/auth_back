<?php

declare(strict_types=1);

namespace Lab\Domain\Access;

use Lab\Domain\Identity\UserId;

/**
 * Puerto de roles (identidad por nombre, Opción B para usuarios).
 *
 * currentRolesOf() devuelve los roles con permisos ya cargados: en Eloquent
 * se resuelve con eager load para no generar N+1 en cada autorización
 * (la latencia es variable experimental, no debe contaminarse).
 */
interface RoleRepository
{
    /**
     * @return list<Role> Roles vigentes del usuario, con permisos cargados.
     */
    public function currentRolesOf(UserId $userId): array;

    /**
     * @return list<Role> Todos los roles definidos en el sistema.
     */
    public function all(): array;

    public function findByName(string $name): ?Role;

    public function save(Role $role): void;

    public function delete(string $roleName): void;

    /**
     * @param list<PermissionName> $permissions
     */
    public function syncPermissions(string $roleName, array $permissions): void;

    public function assignToUser(UserId $userId, string $roleName): void;

    public function revokeFromUser(UserId $userId, string $roleName): void;
}
