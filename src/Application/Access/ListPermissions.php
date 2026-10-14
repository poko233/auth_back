<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use Lab\Domain\Access\Permission;
use Lab\Domain\Access\PermissionRepository;

/**
 * Lista permisos del sistema.
 */
final class ListPermissions
{
    public function __construct(
        private PermissionRepository $permissions,
    ) {
    }

    /**
     * @return list<Permission>
     */
    public function execute(): array
    {
        return $this->permissions->all();
    }
}
