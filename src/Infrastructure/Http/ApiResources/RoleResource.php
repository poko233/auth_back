<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\ApiResources;

use Lab\Domain\Access\Role;

/**
 * Representación JSON de un rol con sus permisos.
 */
final class RoleResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Role $role): array
    {
        return [
            'name' => $role->name(),
            'description' => $role->description(),
            'is_system' => $role->isSystem(),
            'permissions' => array_map(
                static fn ($permission): string => $permission->name()->value(),
                $role->permissions(),
            ),
        ];
    }
}
