<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use Lab\Domain\Access\Exception\PermissionInUse;
use Lab\Domain\Access\Exception\SystemPermissionProtected;
use Lab\Domain\Access\Permission;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Access\PermissionRepository;
use Lab\Infrastructure\Persistence\Eloquent\Mappers\PermissionMapper;
use Lab\Infrastructure\Persistence\Eloquent\Models\PermissionModel;

/**
 * Implementación Eloquent del puerto de permisos.
 */
final class EloquentPermissionRepository implements PermissionRepository
{
    private const COLUMNS = ['id', 'name', 'description', 'is_system', 'created_at', 'updated_at'];

    public function __construct(
        private PermissionMapper $mapper,
    ) {
    }

    public function save(Permission $permission): Permission
    {
        $model = PermissionModel::query()->select(self::COLUMNS)->where('name', $permission->name()->value())->first();

        if ($model === null) {
            $model = new PermissionModel();
            $model->setAttribute('name', $permission->name()->value());
        }

        $model->setAttribute('description', $permission->description());
        $model->setAttribute('is_system', $permission->isSystem());
        $model->save();
        $model->refresh();

        return $this->mapper->toDomain($model);
    }

    public function findById(int $id): ?Permission
    {
        $model = PermissionModel::query()->select(self::COLUMNS)->where('id', $id)->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findByName(PermissionName $name): ?Permission
    {
        $model = PermissionModel::query()->select(self::COLUMNS)->where('name', $name->value())->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function all(): array
    {
        $models = PermissionModel::query()->select(self::COLUMNS)->orderBy('name')->get();
        $permissions = [];

        foreach ($models as $model) {
            $permissions[] = $this->mapper->toDomain($model);
        }

        return $permissions;
    }

    public function delete(PermissionName $name): void
    {
        $model = PermissionModel::query()->select(self::COLUMNS)->where('name', $name->value())->first();

        if ($model === null) {
            return;
        }

        if ((bool) $model->getAttribute('is_system')) {
            throw new SystemPermissionProtected("El permiso {$name->value()} es de sistema.");
        }

        $inUse = (int) $model->roles()->count() > 0;

        if ($inUse) {
            throw new PermissionInUse("El permiso {$name->value()} está asignado a roles.");
        }

        $model->delete();
    }
}
