<?php

declare(strict_types=1);

namespace Lab\Domain\Experiment;

use DateTimeImmutable;

/**
 * Evento del experimento: role_change, error, etc.
 */
final readonly class ExperimentEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public ?int $id,
        public int $experimentId,
        public string $eventType,
        public array $payload,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
