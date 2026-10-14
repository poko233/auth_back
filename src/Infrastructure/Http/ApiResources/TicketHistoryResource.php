<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\ApiResources;

use Lab\Domain\Ticketing\TicketHistoryEntry;

/**
 * Representación JSON de una entrada de bitácora.
 */
final class TicketHistoryResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(TicketHistoryEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'ticket_id' => $entry->ticketId->value(),
            'actor_id' => $entry->actorId,
            'action' => $entry->action,
            'from_value' => $entry->fromValue,
            'to_value' => $entry->toValue,
            'created_at' => $entry->createdAt->format(DATE_ATOM),
        ];
    }
}
