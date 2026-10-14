<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use InvalidArgumentException;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Access\PermissionRepository;
use Lab\Domain\Access\Role;
use Lab\Domain\Access\RoleRepository;

/**
 * Crea un rol dinámico (is_system=false, libre gestión).
 */
final class CreateRole
{
    public function __construct(
        private RoleRepository $roles,
        private PermissionRepository $permissions,
    ) {
    }

    /**
     * @param list<string> $permissionNames
     */
    public function execute(string $name, ?string $description = null, array $permissionNames = []): Role
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new InvalidArgumentException('El nombre del rol no puede estar vacío.');
        }

        if ($this->roles->findByName($trimmed) !== null) {
            throw new InvalidArgumentException("El rol {$trimmed} ya existe.");
        }

        $permissions = [];

        foreach ($permissionNames as $permissionName) {
            $permission = $this->permissions->findByName(PermissionName::fromString($permissionName));

            if ($permission === null) {
                throw new InvalidArgumentException("Permiso desconocido: {$permissionName}.");
            }

            $permissions[] = $permission;
        }

        $role = Role::create($trimmed, $description, false, $permissions);
        $this->roles->save($role);

        return $role;
    }
}
