<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing;

/**
 * Puerto de vistas de tickets.
 */
interface TicketViewRepository
{
    public function record(TicketView $view): void;

    /**
     * @return list<TicketView>
     */
    public function listForTicket(TicketId $ticketId): array;
}
