<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\ApiKey;

use InvalidArgumentException;

/**
 * Value Object del id de una API Key (autoincremental).
 */
final readonly class ApiKeyId
{
    public function __construct(private int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('ApiKeyId debe ser un entero positivo.');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
