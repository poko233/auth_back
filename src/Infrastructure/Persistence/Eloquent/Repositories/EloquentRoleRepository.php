<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use Lab\Domain\Access\Exception\RoleInUse;
use Lab\Domain\Access\Exception\RoleNotFound;
use Lab\Domain\Access\Exception\SystemRoleProtected;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Access\Role;
use Lab\Domain\Access\RoleRepository;
use Lab\Domain\Identity\UserId;
use Lab\Infrastructure\Persistence\Eloquent\Mappers\PermissionMapper;
use Lab\Infrastructure\Persistence\Eloquent\Mappers\RoleMapper;
use Lab\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use Lab\Infrastructure\Persistence\Eloquent\Models\RoleModel;

/**
 * Implementación Eloquent del puerto de roles.
 *
 * currentRolesOf carga permisos con eager load (una sola query extra):
 * N+1 prohibido porque la latencia es variable experimental.
 */
final class EloquentRoleRepository implements RoleRepository
{
    public function __construct(
        private RoleMapper $mapper,
        private PermissionMapper $permissions,
    ) {
    }

    public function currentRolesOf(UserId $userId): array
    {
        if (! ctype_digit($userId->value())) {
            return [];
        }

        $models = RoleModel::query()
            ->select(['roles.id', 'roles.name', 'roles.description', 'roles.is_system', 'roles.created_at', 'roles.updated_at'])
            ->with('permissions')
            ->whereHas('users', fn ($query): mixed => $query->where('users.id', (int) $userId->value()))
            ->get();

        $roles = [];

        foreach ($models as $model) {
            $roles[] = $this->mapper->toDomain($model);
        }

        return $roles;
    }

    public function all(): array
    {
        $models = RoleModel::query()
            ->select(['id', 'name', 'description', 'is_system', 'created_at', 'updated_at'])
            ->with('permissions')
            ->orderBy('name')
            ->get();

        $roles = [];

        foreach ($models as $model) {
            $roles[] = $this->mapper->toDomain($model);
        }

        return $roles;
    }

    public function findByName(string $name): ?Role
    {
        $model = RoleModel::query()
            ->select(['id', 'name', 'description', 'is_system', 'created_at', 'updated_at'])
            ->with('permissions')
            ->where('name', $name)
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function save(Role $role): void
    {
        $model = RoleModel::query()->where('name', $role->name())->first();

        if ($model === null) {
            $model = new RoleModel();
            $model->setAttribute('name', $role->name());
        }

        $model->setAttribute('description', $role->description());
        $model->setAttribute('is_system', $role->isSystem());
        $model->save();

        $permissionIds = [];

        foreach ($role->permissions() as $permission) {
            $permissionModel = PermissionModel::query()->select(['id'])->where('name', $permission->name()->value())->first();

            if ($permissionModel !== null) {
                $permissionIds[] = (int) $permissionModel->getAttribute('id');
            }
        }

        $model->permissions()->sync($permissionIds);
    }

    public function delete(string $roleName): void
    {
        $model = RoleModel::query()->where('name', $roleName)->first();

        if ($model === null) {
            return;
        }

        if ((bool) $model->getAttribute('is_system')) {
            throw new SystemRoleProtected("El rol {$roleName} es de sistema.");
        }

        if ((int) $model->users()->count() > 0) {
            throw new RoleInUse("El rol {$roleName} está asignado a usuarios.");
        }

        $model->permissions()->detach();
        $model->delete();
    }

    public function syncPermissions(string $roleName, array $permissions): void
    {
        $model = RoleModel::query()->where('name', $roleName)->first();

        if ($model === null) {
            throw new RoleNotFound("Rol desconocido: {$roleName}.");
        }

        $permissionIds = [];

        foreach ($permissions as $name) {
            $permissionModel = PermissionModel::query()->select(['id'])->where('name', $name->value())->first();

            if ($permissionModel !== null) {
                $permissionIds[] = (int) $permissionModel->getAttribute('id');
            }
        }

        $model->permissions()->sync($permissionIds);
    }

    public function assignToUser(UserId $userId, string $roleName): void
    {
        if (! ctype_digit($userId->value())) {
            throw new RoleNotFound("Usuario inválido: {$userId->value()}.");
        }

        $model = RoleModel::query()->select(['id'])->where('name', $roleName)->first();

        if ($model === null) {
            throw new RoleNotFound("Rol desconocido: {$roleName}.");
        }

        $model->users()->syncWithoutDetaching([(int) $userId->value() => ['assigned_at' => now()]]);
    }

    public function revokeFromUser(UserId $userId, string $roleName): void
    {
        if (! ctype_digit($userId->value())) {
            return;
        }

        $model = RoleModel::query()->select(['id'])->where('name', $roleName)->first();

        if ($model === null) {
            return;
        }

        $model->users()->detach((int) $userId->value());
    }
}
