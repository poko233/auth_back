<?php

declare(strict_types=1);

namespace Lab\Application\Identity;

use InvalidArgumentException;
use Lab\Domain\Identity\Exception\UserNotFound;
use Lab\Domain\Identity\PasswordHasher;
use Lab\Domain\Identity\User;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Identity\UserRepository;

/**
 * Cambia la contraseña de un usuario (recibe plano, persiste hash).
 */
final class ChangeUserPassword
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
    ) {
    }

    public function execute(UserId $id, string $plainPassword): User
    {
        if (trim($plainPassword) === '') {
            throw new InvalidArgumentException('La contraseña no puede estar vacía.');
        }

        $user = $this->users->findById($id);

        if ($user === null) {
            throw new UserNotFound("Usuario no encontrado: {$id->value()}.");
        }

        $user->changePasswordHash($this->hasher->hash($plainPassword));

        return $this->users->save($user);
    }
}
