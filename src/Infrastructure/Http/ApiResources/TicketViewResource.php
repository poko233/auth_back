<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\ApiResources;

use Lab\Domain\Ticketing\TicketView;

/**
 * Representación JSON de una vista de ticket.
 */
final class TicketViewResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(TicketView $view): array
    {
        return [
            'id' => $view->id,
            'ticket_id' => $view->ticketId->value(),
            'user_id' => $view->userId,
            'viewed_at' => $view->viewedAt->format(DATE_ATOM),
        ];
    }
}
