<?php

declare(strict_types=1);

namespace Lab\Application\Experiment;

use Lab\Domain\Experiment\ExperimentEvent;
use Lab\Domain\Experiment\ExperimentRepository;
use Lab\Domain\Shared\Clock;

/**
 * Registra un evento del experimento (role_change, error, ...).
 */
final class RecordEvent
{
    public function __construct(
        private ExperimentRepository $experiments,
        private Clock $clock,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $experimentId, string $eventType, array $payload = []): void
    {
        $this->experiments->recordEvent(new ExperimentEvent(
            null,
            $experimentId,
            $eventType,
            $payload,
            $this->clock->now(),
        ));
    }
}
