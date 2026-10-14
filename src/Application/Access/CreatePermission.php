<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use InvalidArgumentException;
use Lab\Domain\Access\Permission;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Access\PermissionRepository;

/**
 * Crea un permiso dinámico (is_system=false).
 */
final class CreatePermission
{
    public function __construct(
        private PermissionRepository $permissions,
    ) {
    }

    public function execute(string $name, ?string $description = null): Permission
    {
        $nameVo = PermissionName::fromString($name);

        if ($this->permissions->findByName($nameVo) !== null) {
            throw new InvalidArgumentException("El permiso {$name} ya existe.");
        }

        $permission = Permission::create($nameVo, $description, false);

        return $this->permissions->save($permission);
    }
}
