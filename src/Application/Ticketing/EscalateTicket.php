<?php

declare(strict_types=1);

namespace Lab\Application\Ticketing;

use Lab\Domain\Access\Exception\RoleNotFound;
use Lab\Domain\Access\RoleRepository;
use Lab\Domain\Shared\Clock;
use Lab\Domain\Shared\TransactionManager;
use Lab\Domain\Ticketing\Exception\TicketNotFound;
use Lab\Domain\Ticketing\Ticket;
use Lab\Domain\Ticketing\TicketEscalation;
use Lab\Domain\Ticketing\TicketEscalationRepository;
use Lab\Domain\Ticketing\TicketHistoryEntry;
use Lab\Domain\Ticketing\TicketHistoryRepository;
use Lab\Domain\Ticketing\TicketId;
use Lab\Domain\Ticketing\TicketRepository;

/**
 * Escala un ticket a otro rol; graba escalamiento e historial en transacción.
 */
final class EscalateTicket
{
    public function __construct(
        private TicketRepository $tickets,
        private TicketEscalationRepository $escalations,
        private TicketHistoryRepository $history,
        private RoleRepository $roles,
        private TransactionManager $transactions,
        private Clock $clock,
    ) {
    }

    public function execute(TicketId $id, string $toRole, int $escalatedBy, ?string $note = null): Ticket
    {
        if ($this->roles->findByName($toRole) === null) {
            throw new RoleNotFound("Rol desconocido: {$toRole}.");
        }

        $ticket = $this->tickets->findById($id);

        if ($ticket === null) {
            throw new TicketNotFound("Ticket no encontrado: {$id->value()}.");
        }

        $fromRole = $ticket->assignedRole();
        $now = $this->clock->now();
        $ticket->escalate($toRole, $now);

        return $this->transactions->transaction(function () use ($ticket, $fromRole, $toRole, $escalatedBy, $note, $now): Ticket {
            $persisted = $this->tickets->save($ticket);

            $this->escalations->record(new TicketEscalation(
                null,
                $persisted->id(),
                $fromRole,
                $toRole,
                $escalatedBy,
                $now,
                $note,
            ));

            $this->history->record(new TicketHistoryEntry(
                null,
                $persisted->id(),
                $escalatedBy,
                'escalated',
                $fromRole,
                $toRole,
                $now,
            ));

            return $persisted;
        });
    }
}
