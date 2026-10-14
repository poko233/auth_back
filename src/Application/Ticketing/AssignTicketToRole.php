<?php

declare(strict_types=1);

namespace Lab\Application\Ticketing;

use Lab\Domain\Access\Exception\RoleNotFound;
use Lab\Domain\Access\RoleRepository;
use Lab\Domain\Shared\Clock;
use Lab\Domain\Shared\TransactionManager;
use Lab\Domain\Ticketing\Exception\TicketNotFound;
use Lab\Domain\Ticketing\Ticket;
use Lab\Domain\Ticketing\TicketHistoryEntry;
use Lab\Domain\Ticketing\TicketHistoryRepository;
use Lab\Domain\Ticketing\TicketId;
use Lab\Domain\Ticketing\TicketRepository;

/**
 * Asigna un ticket a un rol; graba 'assigned' en el historial.
 */
final class AssignTicketToRole
{
    public function __construct(
        private TicketRepository $tickets,
        private TicketHistoryRepository $history,
        private RoleRepository $roles,
        private TransactionManager $transactions,
        private Clock $clock,
    ) {
    }

    public function execute(TicketId $id, ?string $roleName, ?int $actorId = null): Ticket
    {
        if ($roleName !== null && $this->roles->findByName($roleName) === null) {
            throw new RoleNotFound("Rol desconocido: {$roleName}.");
        }

        $ticket = $this->tickets->findById($id);

        if ($ticket === null) {
            throw new TicketNotFound("Ticket no encontrado: {$id->value()}.");
        }

        $before = $ticket->assignedRole();
        $ticket->assignToRole($roleName);
        $now = $this->clock->now();

        return $this->transactions->transaction(function () use ($ticket, $before, $roleName, $actorId, $now): Ticket {
            $persisted = $this->tickets->save($ticket);

            $this->history->record(new TicketHistoryEntry(
                null,
                $persisted->id(),
                $actorId,
                'assigned',
                $before,
                $roleName,
                $now,
            ));

            return $persisted;
        });
    }
}
