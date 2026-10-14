<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\ApiResources;

use Lab\Domain\Ticketing\Ticket;

/**
 * Representación JSON de un ticket.
 */
final class TicketResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id()?->value(),
            'title' => $ticket->title(),
            'description' => $ticket->description(),
            'status' => $ticket->status()->value,
            'priority' => $ticket->priority()->value,
            'created_by' => $ticket->createdBy(),
            'assigned_to' => $ticket->assignedTo(),
            'assigned_role' => $ticket->assignedRole(),
            'escalated_at' => $ticket->escalatedAt()?->format(DATE_ATOM),
            'resolved_at' => $ticket->resolvedAt()?->format(DATE_ATOM),
            'closed_at' => $ticket->closedAt()?->format(DATE_ATOM),
            'deleted_at' => $ticket->deletedAt()?->format(DATE_ATOM),
        ];
    }
}
