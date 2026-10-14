<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing;

use DateTimeImmutable;

/**
 * Registro de escalamiento de un ticket entre roles.
 */
final readonly class TicketEscalation
{
    public function __construct(
        public ?int $id,
        public TicketId $ticketId,
        public ?string $fromRole,
        public ?string $toRole,
        public int $escalatedBy,
        public DateTimeImmutable $escalatedAt,
        public ?string $note,
    ) {
    }
}
