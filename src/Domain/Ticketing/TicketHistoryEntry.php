<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing;

use DateTimeImmutable;

/**
 * Entrada de la bitácora de mutaciones de un ticket.
 *
 * Acciones: created | updated | assigned | escalated | status_changed | closed | deleted.
 * actorId nulo = acción del sistema.
 */
final readonly class TicketHistoryEntry
{
    public function __construct(
        public ?int $id,
        public TicketId $ticketId,
        public ?int $actorId,
        public string $action,
        public ?string $fromValue,
        public ?string $toValue,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
