<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use Lab\Domain\Access\Exception\PermissionNotFound;
use Lab\Domain\Access\Exception\SystemPermissionProtected;
use Lab\Domain\Access\Permission;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Access\PermissionRepository;

/**
 * Actualiza la descripción de un permiso libre.
 */
final class UpdatePermission
{
    public function __construct(
        private PermissionRepository $permissions,
    ) {
    }

    public function execute(string $name, ?string $description): Permission
    {
        $permission = $this->permissions->findByName(PermissionName::fromString($name));

        if ($permission === null) {
            throw new PermissionNotFound("Permiso desconocido: {$name}.");
        }

        if ($permission->isSystem()) {
            throw new SystemPermissionProtected("El permiso {$name} es de sistema.");
        }

        $permission->rename($permission->name(), $description);

        return $this->permissions->save($permission);
    }
}
