<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use Lab\Domain\Access\Exception\RoleNotFound;
use Lab\Domain\Access\Exception\SystemRoleProtected;
use Lab\Domain\Access\RoleRepository;

/**
 * Borra un rol libre (los de sistema están protegidos).
 */
final class DeleteRole
{
    public function __construct(
        private RoleRepository $roles,
    ) {
    }

    public function execute(string $name): void
    {
        $role = $this->roles->findByName($name);

        if ($role === null) {
            throw new RoleNotFound("Rol desconocido: {$name}.");
        }

        if ($role->isSystem()) {
            throw new SystemRoleProtected("El rol {$name} es de sistema.");
        }

        $this->roles->delete($name);
    }
}
