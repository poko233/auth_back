<?php

declare(strict_types=1);

namespace Lab\Application\Ticketing;

use Lab\Domain\Shared\Clock;
use Lab\Domain\Shared\TransactionManager;
use Lab\Domain\Ticketing\Exception\TicketNotFound;
use Lab\Domain\Ticketing\Ticket;
use Lab\Domain\Ticketing\TicketHistoryEntry;
use Lab\Domain\Ticketing\TicketHistoryRepository;
use Lab\Domain\Ticketing\TicketId;
use Lab\Domain\Ticketing\TicketRepository;
use Lab\Domain\Ticketing\TicketStatus;

/**
 * Cambia el estado de un ticket; graba 'status_changed' (o 'closed').
 */
final class ChangeTicketStatus
{
    public function __construct(
        private TicketRepository $tickets,
        private TicketHistoryRepository $history,
        private TransactionManager $transactions,
        private Clock $clock,
    ) {
    }

    public function execute(TicketId $id, TicketStatus $to, ?int $actorId = null): Ticket
    {
        $ticket = $this->tickets->findById($id);

        if ($ticket === null) {
            throw new TicketNotFound("Ticket no encontrado: {$id->value()}.");
        }

        $before = $ticket->status()->value;
        $now = $this->clock->now();
        $ticket->changeStatus($to, $now);

        return $this->transactions->transaction(function () use ($ticket, $before, $to, $actorId, $now): Ticket {
            $persisted = $this->tickets->save($ticket);

            $this->history->record(new TicketHistoryEntry(
                null,
                $persisted->id(),
                $actorId,
                $to === TicketStatus::CLOSED ? 'closed' : 'status_changed',
                $before,
                $to->value,
                $now,
            ));

            return $persisted;
        });
    }
}
