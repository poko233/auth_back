<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Mappers;

use Lab\Domain\Access\Role;
use Lab\Infrastructure\Persistence\Eloquent\Models\RoleModel;

/**
 * Traductor entre RoleModel (Eloquent, con permisos en eager) y Role (Domain).
 */
final class RoleMapper
{
    public function __construct(
        private PermissionMapper $permissions,
    ) {
    }

    public function toDomain(RoleModel $model): Role
    {
        $permissions = [];

        foreach ($model->getRelation('permissions') as $permissionModel) {
            $permissions[] = $this->permissions->toDomain($permissionModel);
        }

        return Role::create(
            (string) $model->getAttribute('name'),
            $model->getAttribute('description') !== null ? (string) $model->getAttribute('description') : null,
            (bool) $model->getAttribute('is_system'),
            $permissions,
        );
    }
}
