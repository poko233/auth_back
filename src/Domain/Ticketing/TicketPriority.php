<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing;

/**
 * Prioridades de un ticket.
 */
enum TicketPriority: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
    case CRITICAL = 'CRITICAL';
}
