<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use Lab\Domain\Access\Exception\PermissionNotFound;
use Lab\Domain\Access\Exception\SystemPermissionProtected;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Access\PermissionRepository;

/**
 * Borra un permiso libre (el repo rechaza los asignados con PermissionInUse).
 */
final class DeletePermission
{
    public function __construct(
        private PermissionRepository $permissions,
    ) {
    }

    public function execute(string $name): void
    {
        $nameVo = PermissionName::fromString($name);
        $permission = $this->permissions->findByName($nameVo);

        if ($permission === null) {
            throw new PermissionNotFound("Permiso desconocido: {$name}.");
        }

        if ($permission->isSystem()) {
            throw new SystemPermissionProtected("El permiso {$name} es de sistema.");
        }

        $this->permissions->delete($nameVo);
    }
}
