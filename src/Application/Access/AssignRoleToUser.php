<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use Lab\Domain\Access\Exception\RoleNotFound;
use Lab\Domain\Access\RoleRepository;
use Lab\Domain\Identity\Exception\UserNotFound;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Identity\UserRepository;

/**
 * Asigna un rol a un usuario (escalado de privilegios del experimento).
 */
final class AssignRoleToUser
{
    public function __construct(
        private UserRepository $users,
        private RoleRepository $roles,
    ) {
    }

    public function execute(UserId $userId, string $roleName): void
    {
        if ($this->users->findById($userId) === null) {
            throw new UserNotFound("Usuario no encontrado: {$userId->value()}.");
        }

        if ($this->roles->findByName($roleName) === null) {
            throw new RoleNotFound("Rol desconocido: {$roleName}.");
        }

        $this->roles->assignToUser($userId, $roleName);
    }
}
