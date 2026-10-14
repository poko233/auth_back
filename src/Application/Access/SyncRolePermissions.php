<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use InvalidArgumentException;
use Lab\Domain\Access\Exception\RoleNotFound;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Access\PermissionRepository;
use Lab\Domain\Access\RoleRepository;

/**
 * Reemplaza los permisos de un rol.
 */
final class SyncRolePermissions
{
    public function __construct(
        private RoleRepository $roles,
        private PermissionRepository $permissions,
    ) {
    }

    /**
     * @param list<string> $permissionNames
     */
    public function execute(string $roleName, array $permissionNames): void
    {
        if ($this->roles->findByName($roleName) === null) {
            throw new RoleNotFound("Rol desconocido: {$roleName}.");
        }

        $names = [];

        foreach ($permissionNames as $permissionName) {
            $permission = $this->permissions->findByName(PermissionName::fromString($permissionName));

            if ($permission === null) {
                throw new InvalidArgumentException("Permiso desconocido: {$permissionName}.");
            }

            $names[] = $permission->name();
        }

        $this->roles->syncPermissions($roleName, $names);
    }
}
