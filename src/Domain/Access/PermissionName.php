<?php

declare(strict_types=1);

namespace Lab\Domain\Access;

use InvalidArgumentException;

/**
 * Value Object del nombre de un permiso.
 *
 * Valida formato (mayúsculas, dígitos y guion bajo, 2–32 chars) pero no
 * existencia: eso lo resuelve PermissionRepository. Cero strings mágicos
 * hacia AuthorizeAction.
 */
final readonly class PermissionName
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if (preg_match('/^[A-Z][A-Z0-9_]{1,31}$/', $trimmed) !== 1) {
            throw new InvalidArgumentException('Nombre de permiso inválido: debe usar mayúsculas, dígitos y guion bajo (2–32 caracteres).');
        }

        $this->value = $trimmed;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
