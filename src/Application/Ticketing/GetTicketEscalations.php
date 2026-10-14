<?php

declare(strict_types=1);

namespace Lab\Application\Ticketing;

use Lab\Domain\Ticketing\TicketEscalation;
use Lab\Domain\Ticketing\TicketEscalationRepository;
use Lab\Domain\Ticketing\TicketId;

/**
 * Devuelve los escalamientos registrados de un ticket.
 */
final class GetTicketEscalations
{
    public function __construct(
        private TicketEscalationRepository $escalations,
    ) {
    }

    /**
     * @return list<TicketEscalation>
     */
    public function execute(TicketId $ticketId): array
    {
        return $this->escalations->listForTicket($ticketId);
    }
}
