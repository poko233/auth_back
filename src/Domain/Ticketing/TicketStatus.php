<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing;

/**
 * Estados del ciclo de vida de un ticket.
 */
enum TicketStatus: string
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case ESCALATED = 'ESCALATED';
    case RESOLVED = 'RESOLVED';
    case CLOSED = 'CLOSED';
}
