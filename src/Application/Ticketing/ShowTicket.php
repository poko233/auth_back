<?php

declare(strict_types=1);

namespace Lab\Application\Ticketing;

use Lab\Domain\Ticketing\Exception\TicketNotFound;
use Lab\Domain\Ticketing\Ticket;
use Lab\Domain\Ticketing\TicketId;
use Lab\Domain\Ticketing\TicketRepository;

/**
 * Muestra un ticket por id (los eliminados responden como no encontrados).
 */
final class ShowTicket
{
    public function __construct(
        private TicketRepository $tickets,
    ) {
    }

    public function execute(TicketId $id): Ticket
    {
        $ticket = $this->tickets->findById($id);

        if ($ticket === null) {
            throw new TicketNotFound("Ticket no encontrado: {$id->value()}.");
        }

        return $ticket;
    }
}
