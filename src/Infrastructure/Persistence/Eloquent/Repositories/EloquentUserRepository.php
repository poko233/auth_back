<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use Lab\Domain\Identity\Email;
use Lab\Domain\Identity\User;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Identity\UserRepository;
use Lab\Infrastructure\Persistence\Eloquent\Mappers\UserMapper;
use Lab\Infrastructure\Persistence\Eloquent\Models\UserModel;

/**
 * Implementación Eloquent del puerto de usuarios.
 */
final class EloquentUserRepository implements UserRepository
{
    private const COLUMNS = ['id', 'name', 'email', 'password', 'is_active', 'created_at', 'updated_at'];

    public function __construct(
        private UserMapper $mapper,
    ) {
    }

    public function save(User $user): User
    {
        $id = $user->id()->value();

        $model = ctype_digit($id)
            ? UserModel::query()->select(self::COLUMNS)->where('id', (int) $id)->first()
            : UserModel::query()->select(self::COLUMNS)->where('email', $user->email()->value())->first();

        if ($model === null) {
            $model = new UserModel();
        }

        $model->setAttribute('name', $user->name());
        $model->setAttribute('email', $user->email()->value());

        if ($user->passwordHash() !== null) {
            $model->setAttribute('password', $user->passwordHash());
        }

        $model->setAttribute('is_active', $user->isActive());
        $model->save();
        $model->refresh();

        return $this->mapper->toDomain($model);
    }

    public function findById(UserId $id): ?User
    {
        if (! ctype_digit($id->value())) {
            return null;
        }

        $model = UserModel::query()->select(self::COLUMNS)->where('id', (int) $id->value())->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findByEmail(Email $email): ?User
    {
        $model = UserModel::query()->select(self::COLUMNS)->where('email', $email->value())->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function listAll(): array
    {
        $models = UserModel::query()->select(self::COLUMNS)->orderBy('id')->get();
        $users = [];

        foreach ($models as $model) {
            $users[] = $this->mapper->toDomain($model);
        }

        return $users;
    }

    public function delete(UserId $id): void
    {
        if (! ctype_digit($id->value())) {
            return;
        }

        UserModel::query()->where('id', (int) $id->value())->delete();
    }
}
