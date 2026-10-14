<?php

declare(strict_types=1);

namespace Lab\Application\Ticketing;

use Lab\Domain\Ticketing\TicketId;
use Lab\Domain\Ticketing\TicketView;
use Lab\Domain\Ticketing\TicketViewRepository;

/**
 * Devuelve las vistas registradas de un ticket.
 */
final class GetTicketViews
{
    public function __construct(
        private TicketViewRepository $views,
    ) {
    }

    /**
     * @return list<TicketView>
     */
    public function execute(TicketId $ticketId): array
    {
        return $this->views->listForTicket($ticketId);
    }
}
