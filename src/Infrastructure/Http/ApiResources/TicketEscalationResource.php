<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\ApiResources;

use Lab\Domain\Ticketing\TicketEscalation;

/**
 * Representación JSON de un escalamiento.
 */
final class TicketEscalationResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(TicketEscalation $escalation): array
    {
        return [
            'id' => $escalation->id,
            'ticket_id' => $escalation->ticketId->value(),
            'from_role' => $escalation->fromRole,
            'to_role' => $escalation->toRole,
            'escalated_by' => $escalation->escalatedBy,
            'escalated_at' => $escalation->escalatedAt->format(DATE_ATOM),
            'note' => $escalation->note,
        ];
    }
}
