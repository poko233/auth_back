<?php

declare(strict_types=1);

namespace Lab\Domain\Access;

/**
 * Puerto CRUD de permisos.
 */
interface PermissionRepository
{
    public function save(Permission $permission): Permission;

    public function findById(int $id): ?Permission;

    public function findByName(PermissionName $name): ?Permission;

    /**
     * @return list<Permission>
     */
    public function all(): array;

    public function delete(PermissionName $name): void;
}
