<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing;

/**
 * Puerto de la bitácora de mutaciones de tickets.
 */
interface TicketHistoryRepository
{
    public function record(TicketHistoryEntry $entry): void;

    /**
     * @return list<TicketHistoryEntry>
     */
    public function listForTicket(TicketId $ticketId): array;
}
