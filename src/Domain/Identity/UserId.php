<?php

declare(strict_types=1);

namespace Lab\Domain\Identity;

use InvalidArgumentException;

/**
 * Value Object que representa el identificador único de un usuario.
 *
 * - Inmutable y comparado por valor.
 * - Sin dependencias de framework: Domain no conoce Laravel, ni Eloquent, ni HTTP.
 * - Independiente del tipo de credencial: los cuatro mecanismos producen un UserId
 *   a partir de fuentes distintas (API Key, usuario/contraseña, claims de JWT, sub de OAuth).
 */
final readonly class UserId
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('UserId no puede estar vacío.');
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