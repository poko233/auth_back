<?php

declare(strict_types=1);

namespace Lab\Application\Identity;

use Lab\Domain\Identity\User;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Identity\UserRepository;

/**
 * Busca el usuario autenticado actualmente.
 */
final class FindCurrentUser
{
    public function __construct(
        private UserRepository $users,
    ) {
    }

    public function execute(UserId $userId): ?User
    {
        return $this->users->findById($userId);
    }
}