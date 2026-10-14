<?php

declare(strict_types=1);

namespace Lab\Application\Identity;

use InvalidArgumentException;
use Lab\Domain\Identity\Email;
use Lab\Domain\Identity\PasswordHasher;
use Lab\Domain\Identity\User;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Identity\UserRepository;

/**
 * Registra un usuario con contraseña hasheada.
 */
final class RegisterUser
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
    ) {
    }

    public function execute(string $name, string $email, string $plainPassword): User
    {
        if (trim($plainPassword) === '') {
            throw new InvalidArgumentException('La contraseña no puede estar vacía.');
        }

        $emailVo = Email::fromString($email);

        if ($this->users->findByEmail($emailVo) !== null) {
            throw new InvalidArgumentException('El email ya está registrado.');
        }

        $user = User::create(
            UserId::fromString(uniqid('user-', true)),
            $emailVo,
            $name,
            $this->hasher->hash($plainPassword),
        );

        return $this->users->save($user);
    }
}
