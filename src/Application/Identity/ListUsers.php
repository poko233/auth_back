<?php

declare(strict_types=1);

namespace Lab\Application\Identity;

use Lab\Domain\Identity\User;
use Lab\Domain\Identity\UserRepository;

/**
 * Lista usuarios.
 */
final class ListUsers
{
    public function __construct(
        private UserRepository $users,
    ) {
    }

    /**
     * @return list<User>
     */
    public function execute(): array
    {
        return $this->users->listAll();
    }
}
