<?php

declare(strict_types=1);

namespace Lab\Domain\Access;

use InvalidArgumentException;

/**
 * Entidad Permission (dinámica, CRUD por API).
 *
 * Los permisos base (READ, WRITE, DELETE, ADMIN) nacen con isSystem=true y no
 * se pueden borrar ni renombrar: sin esa regla un admin podría borrar READ y
 * romper todo el sistema.
 */
final class Permission
{
    private function __construct(
        private ?int $id,
        private PermissionName $name,
        private ?string $description,
        private bool $isSystem,
    ) {
    }

    public static function create(PermissionName $name, ?string $description = null, bool $isSystem = false): self
    {
        return new self(null, $name, $description, $isSystem);
    }

    /**
     * Reconstruye un permiso persistido (usado por mappers y fakes).
     */
    public static function reconstitute(int $id, PermissionName $name, ?string $description, bool $isSystem): self
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('El id del permiso debe ser positivo.');
        }

        return new self($id, $name, $description, $isSystem);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): PermissionName
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

    public function rename(PermissionName $name, ?string $description = null): void
    {
        if ($this->isSystem) {
            throw new Exception\SystemPermissionProtected('Los permisos de sistema no se pueden renombrar.');
        }

        $this->name = $name;
        $this->description = $description;
    }
}
