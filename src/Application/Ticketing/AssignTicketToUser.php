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

/**
 * Asigna un ticket a un usuario; graba 'assigned' en el historial.
 */
final class AssignTicketToUser
{
    public function __construct(
        private TicketRepository $tickets,
        private TicketHistoryRepository $history,
        private TransactionManager $transactions,
        private Clock $clock,
    ) {
    }

    public function execute(TicketId $id, ?int $userId, ?int $actorId = null): Ticket
    {
        $ticket = $this->tickets->findById($id);

        if ($ticket === null) {
            throw new TicketNotFound("Ticket no encontrado: {$id->value()}.");
        }

        $before = $ticket->assignedTo() === null ? null : (string) $ticket->assignedTo();
        $ticket->assignToUser($userId);
        $now = $this->clock->now();

        return $this->transactions->transaction(function () use ($ticket, $before, $userId, $actorId, $now): Ticket {
            $persisted = $this->tickets->save($ticket);

            $this->history->record(new TicketHistoryEntry(
                null,
                $persisted->id(),
                $actorId,
                'assigned',
                $before,
                $userId === null ? null : (string) $userId,
                $now,
            ));

            return $persisted;
        });
    }
}
