<?php

declare(strict_types=1);

namespace Lab\Application\Ticketing;

use Lab\Domain\Ticketing\TicketHistoryEntry;
use Lab\Domain\Ticketing\TicketHistoryRepository;
use Lab\Domain\Ticketing\TicketId;

/**
 * Devuelve la bitácora de un ticket ordenada por creación.
 */
final class GetTicketHistory
{
    public function __construct(
        private TicketHistoryRepository $history,
    ) {
    }

    /**
     * @return list<TicketHistoryEntry>
     */
    public function execute(TicketId $ticketId): array
    {
        return $this->history->listForTicket($ticketId);
    }
}
