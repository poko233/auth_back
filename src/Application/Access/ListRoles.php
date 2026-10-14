<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use Lab\Domain\Access\Role;
use Lab\Domain\Access\RoleRepository;

/**
 * Lista roles del sistema.
 */
final class ListRoles
{
    public function __construct(
        private RoleRepository $roles,
    ) {
    }

    /**
     * @return list<Role>
     */
    public function execute(): array
    {
        return $this->roles->all();
    }
}
