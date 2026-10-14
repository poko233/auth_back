<?php

declare(strict_types=1);

namespace Lab\Application\Identity;

use Lab\Domain\Identity\Exception\UserNotFound;
use Lab\Domain\Identity\User;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Identity\UserRepository;

/**
 * Desactiva un usuario sin borrarlo (el experimento necesita revocar acceso
 * conservando el historial).
 */
final class DeactivateUser
{
    public function __construct(
        private UserRepository $users,
    ) {
    }

    public function execute(UserId $id): User
    {
        $user = $this->users->findById($id);

        if ($user === null) {
            throw new UserNotFound("Usuario no encontrado: {$id->value()}.");
        }

        $user->deactivate();

        return $this->users->save($user);
    }
}
