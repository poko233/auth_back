<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use Lab\Domain\Access\Role;
use Lab\Domain\Access\RoleRepository;
use Lab\Domain\Identity\UserId;

final class ListUserRoles
{
    public function __construct(private RoleRepository $roles)
    {
    }

    /** @return list<Role> */
    public function execute(UserId $userId): array
    {
        return $this->roles->currentRolesOf($userId);
    }
}
