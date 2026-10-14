<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing;

use DateTimeImmutable;

/**
 * Registro de vista de un ticket (una fila por vista, sin dedupe).
 */
final readonly class TicketView
{
    public function __construct(
        public ?int $id,
        public TicketId $ticketId,
        public int $userId,
        public DateTimeImmutable $viewedAt,
    ) {
    }
}
