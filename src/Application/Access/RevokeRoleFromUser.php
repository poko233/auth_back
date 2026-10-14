<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use Lab\Domain\Access\RoleRepository;
use Lab\Domain\Identity\Exception\UserNotFound;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Identity\UserRepository;

/**
 * Revoca un rol a un usuario (reducción de privilegios del experimento).
 */
final class RevokeRoleFromUser
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

        $this->roles->revokeFromUser($userId, $roleName);
    }
}
