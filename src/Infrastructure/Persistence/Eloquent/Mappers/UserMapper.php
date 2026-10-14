<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Mappers;

use Lab\Domain\Identity\Email;
use Lab\Domain\Identity\User;
use Lab\Domain\Identity\UserId;
use Lab\Infrastructure\Persistence\Eloquent\Models\UserModel;

/**
 * Traductor entre UserModel (Eloquent) y User (Domain).
 */
final class UserMapper
{
    public function toDomain(UserModel $model): User
    {
        $user = User::create(
            UserId::fromString((string) $model->getAttribute('id')),
            Email::fromString((string) $model->getAttribute('email')),
            (string) $model->getAttribute('name'),
            $model->getAttribute('password') !== null ? (string) $model->getAttribute('password') : null,
        );

        if (! (bool) $model->getAttribute('is_active')) {
            $user->deactivate();
        }

        return $user;
    }
}
