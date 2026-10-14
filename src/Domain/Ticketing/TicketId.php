<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing;

use InvalidArgumentException;

/**
 * Value Object que identifica un ticket (entero autoincremental).
 *
 * - Inmutable y comparado por valor.
 * - BigInt en BD (BIGSERIAL en Postgres): legible en seeds, tests y debug.
 * - La autorización es por rol, no por opacidad del ID, así que la
 *   enumerabilidad del secuencial no afecta al experimento.
 * - Sin ID aún se representa con null a nivel de entidad, nunca con TicketId(0).
 */
final readonly class TicketId
{
    public function __construct(private int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('TicketId debe ser un entero positivo.');
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
