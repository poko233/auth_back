<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use Lab\Domain\Access\Exception\RoleNotFound;
use Lab\Domain\Access\Exception\SystemRoleProtected;
use Lab\Domain\Access\Role;
use Lab\Domain\Access\RoleRepository;

/**
 * Actualiza la descripción de un rol (los de sistema no se tocan).
 */
final class UpdateRole
{
    public function __construct(
        private RoleRepository $roles,
    ) {
    }

    public function execute(string $name, ?string $description): Role
    {
        $role = $this->roles->findByName($name);

        if ($role === null) {
            throw new RoleNotFound("Rol desconocido: {$name}.");
        }

        if ($role->isSystem()) {
            throw new SystemRoleProtected("El rol {$name} es de sistema.");
        }

        $updated = Role::create($role->name(), $description, false, $role->permissions());
        $this->roles->save($updated);

        return $updated;
    }
}
