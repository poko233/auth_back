<?php

declare(strict_types=1);

namespace Lab\Application\Ticketing;

use Lab\Domain\Shared\Clock;
use Lab\Domain\Ticketing\TicketId;
use Lab\Domain\Ticketing\TicketView;
use Lab\Domain\Ticketing\TicketViewRepository;

/**
 * Registra una vista de ticket (una fila por vista, sin dedupe).
 *
 * Best-effort a nivel de controller: si el INSERT falla, el controller lo
 * loggea y la lectura sigue. Este caso de uso propaga errores normalmente.
 */
final class RecordTicketView
{
    public function __construct(
        private TicketViewRepository $views,
        private Clock $clock,
    ) {
    }

    public function execute(TicketId $ticketId, int $userId): void
    {
        $this->views->record(new TicketView(null, $ticketId, $userId, $this->clock->now()));
    }
}
