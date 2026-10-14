<?php

declare(strict_types=1);

namespace Lab\Application\Ticketing;

use Lab\Domain\Shared\Clock;
use Lab\Domain\Shared\TransactionManager;
use Lab\Domain\Ticketing\Ticket;
use Lab\Domain\Ticketing\TicketHistoryEntry;
use Lab\Domain\Ticketing\TicketHistoryRepository;
use Lab\Domain\Ticketing\TicketPriority;
use Lab\Domain\Ticketing\TicketRepository;

/**
 * Crea un ticket y graba 'created' en el historial, en una transacción.
 */
final class CreateTicket
{
    public function __construct(
        private TicketRepository $tickets,
        private TicketHistoryRepository $history,
        private TransactionManager $transactions,
        private Clock $clock,
    ) {
    }

    public function execute(string $title, ?string $description, ?int $createdBy, TicketPriority $priority = TicketPriority::MEDIUM): Ticket
    {
        $ticket = Ticket::create($title, $description, $createdBy, $priority);
        $now = $this->clock->now();

        return $this->transactions->transaction(function () use ($ticket, $now): Ticket {
            $persisted = $this->tickets->save($ticket);

            $this->history->record(new TicketHistoryEntry(
                null,
                $persisted->id(),
                $persisted->createdBy(),
                'created',
                null,
                $persisted->title(),
                $now,
            ));

            return $persisted;
        });
    }
}
