<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing;

/**
 * Puerto de persistencia de tickets (soft delete: findById excluye borrados).
 */
interface TicketRepository
{
    public function save(Ticket $ticket): Ticket;

    public function findById(TicketId $id): ?Ticket;

    /**
     * @return list<Ticket> Excluye soft-deleted por defecto.
     */
    public function listAll(bool $withDeleted = false): array;

    public function delete(Ticket $ticket): void;
}
