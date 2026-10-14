<?php

declare(strict_types=1);

namespace Lab\Application\Identity;

use Lab\Domain\Identity\Email;
use Lab\Domain\Identity\Exception\UserNotFound;
use Lab\Domain\Identity\User;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Identity\UserRepository;

/**
 * Actualiza nombre y/o email de un usuario.
 */
final class UpdateUser
{
    public function __construct(
        private UserRepository $users,
    ) {
    }

    public function execute(UserId $id, ?string $name = null, ?string $email = null): User
    {
        $user = $this->users->findById($id);

        if ($user === null) {
            throw new UserNotFound("Usuario no encontrado: {$id->value()}.");
        }

        if ($name !== null) {
            $user->rename($name);
        }

        if ($email !== null) {
            $user->changeEmail(Email::fromString($email));
        }

        return $this->users->save($user);
    }
}
