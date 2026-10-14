<?php

declare(strict_types=1);

namespace Lab\Application\Ticketing;

use Lab\Domain\Shared\Clock;
use Lab\Domain\Shared\TransactionManager;
use Lab\Domain\Ticketing\Exception\TicketNotFound;
use Lab\Domain\Ticketing\TicketHistoryEntry;
use Lab\Domain\Ticketing\TicketHistoryRepository;
use Lab\Domain\Ticketing\TicketId;
use Lab\Domain\Ticketing\TicketRepository;

/**
 * Elimina un ticket (soft delete) y graba 'deleted' en el historial.
 */
final class DeleteTicket
{
    public function __construct(
        private TicketRepository $tickets,
        private TicketHistoryRepository $history,
        private TransactionManager $transactions,
        private Clock $clock,
    ) {
    }

    public function execute(TicketId $id, ?int $actorId = null): void
    {
        $ticket = $this->tickets->findById($id);

        if ($ticket === null) {
            throw new TicketNotFound("Ticket no encontrado: {$id->value()}.");
        }

        $now = $this->clock->now();
        $ticket->softDelete($now);

        $this->transactions->transaction(function () use ($ticket, $actorId, $now): void {
            $this->tickets->delete($ticket);

            $this->history->record(new TicketHistoryEntry(
                null,
                $ticket->id(),
                $actorId,
                'deleted',
                $ticket->title(),
                null,
                $now,
            ));
        });
    }
}
