<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing;

/**
 * Puerto de escalamientos de tickets.
 */
interface TicketEscalationRepository
{
    public function record(TicketEscalation $escalation): void;

    /**
     * @return list<TicketEscalation>
     */
    public function listForTicket(TicketId $ticketId): array;
}
