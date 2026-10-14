<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Mappers;

use Lab\Domain\Access\Permission;
use Lab\Domain\Access\PermissionName;
use Lab\Infrastructure\Persistence\Eloquent\Models\PermissionModel;

/**
 * Traductor entre PermissionModel (Eloquent) y Permission (Domain).
 */
final class PermissionMapper
{
    public function toDomain(PermissionModel $model): Permission
    {
        return Permission::reconstitute(
            (int) $model->getAttribute('id'),
            PermissionName::fromString((string) $model->getAttribute('name')),
            $model->getAttribute('description') !== null ? (string) $model->getAttribute('description') : null,
            (bool) $model->getAttribute('is_system'),
        );
    }
}
