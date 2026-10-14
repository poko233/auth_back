<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\ApiResources;

use Lab\Domain\Access\Permission;

/**
 * Representación JSON de un permiso.
 */
final class PermissionResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Permission $permission): array
    {
        return [
            'id' => $permission->id(),
            'name' => $permission->name()->value(),
            'description' => $permission->description(),
            'is_system' => $permission->isSystem(),
        ];
    }
}
