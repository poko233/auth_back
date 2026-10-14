<?php

declare(strict_types=1);

namespace Lab\Domain\Access;

use InvalidArgumentException;

/**
 * Rol del laboratorio. Sin RoleId: el nombre es la identidad.
 *
 * Los permisos son entidades (CRUD dinámico): Role lleva list<Permission>.
 */
final class Role
{
    /**
     * @param list<Permission> $permissions
     */
    private function __construct(
        private string $name,
        private ?string $description,
        private bool $isSystem,
        private array $permissions,
    ) {
        if (trim($this->name) === '') {
            throw new InvalidArgumentException('El nombre del rol no puede estar vacío.');
        }
    }

    /**
     * @param list<Permission> $permissions
     */
    public static function create(string $name, ?string $description = null, bool $isSystem = false, array $permissions = []): self
    {
        return new self(trim($name), $description, $isSystem, array_values($permissions));
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return $this->permissions;
    }

    public function allows(PermissionName $permissionName): bool
    {
        foreach ($this->permissions as $permission) {
            if ($permission->name()->equals($permissionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<Permission> $permissions
     */
    public function syncPermissions(array $permissions): void
    {
        $this->permissions = array_values($permissions);
    }
}
