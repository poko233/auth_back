<?php

declare(strict_types=1);

namespace Lab\Application\Ticketing;

use Lab\Domain\Ticketing\Ticket;
use Lab\Domain\Ticketing\TicketRepository;

/**
 * Lista tickets (excluye soft-deleted salvo pedido explícito).
 */
final class ListTickets
{
    public function __construct(
        private TicketRepository $tickets,
    ) {
    }

    /**
     * @return list<Ticket>
     */
    public function execute(bool $withDeleted = false): array
    {
        return $this->tickets->listAll($withDeleted);
    }
}
