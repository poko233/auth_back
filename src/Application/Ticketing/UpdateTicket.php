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
use Lab\Domain\Ticketing\TicketPriority;
use Lab\Domain\Ticketing\TicketRepository;

/**
 * Actualiza título, descripción y/o prioridad; graba 'updated' en historial.
 */
final class UpdateTicket
{
    public function __construct(
        private TicketRepository $tickets,
        private TicketHistoryRepository $history,
        private TransactionManager $transactions,
        private Clock $clock,
    ) {
    }

    public function execute(TicketId $id, ?string $title = null, ?string $description = null, ?TicketPriority $priority = null, ?int $actorId = null): Ticket
    {
        $ticket = $this->tickets->findById($id);

        if ($ticket === null) {
            throw new TicketNotFound("Ticket no encontrado: {$id->value()}.");
        }

        $before = $ticket->title();

        if ($title !== null) {
            $ticket->rename($title, $description ?? $ticket->description());
        }

        if ($priority !== null) {
            $ticket->changePriority($priority);
        }

        $now = $this->clock->now();

        return $this->transactions->transaction(function () use ($ticket, $before, $actorId, $now): Ticket {
            $persisted = $this->tickets->save($ticket);

            $this->history->record(new TicketHistoryEntry(
                null,
                $persisted->id(),
                $actorId,
                'updated',
                $before,
                $persisted->title(),
                $now,
            ));

            return $persisted;
        });
    }
}
