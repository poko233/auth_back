<?php

declare(strict_types=1);

namespace Lab\Domain\Identity;

use InvalidArgumentException;

/**
 * Value Object de correo electrónico.
 *
 * Valida formato en construcción: sin un Email válido no hay User válido.
 * No se agregan VOs para name ni password (sobre-ingeniería para este lab).
 */
final readonly class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim(mb_strtolower($value));

        if ($trimmed === '') {
            throw new InvalidArgumentException('Email no puede estar vacío.');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('Email no puede superar 255 caracteres.');
        }

        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Formato de email inválido.');
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
