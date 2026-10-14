<?php

declare(strict_types=1);

namespace Lab\Domain\Experiment;

use DateTimeImmutable;

/**
 * Métrica de una operación: latencia total y de auth separadas.
 *
 * Con baseline de red ~150-250 ms, el experimento exige N ≥ 30 iteraciones y
 * reporta mediana/p95/desviación, nunca solo promedio.
 */
final readonly class Metric
{
    public function __construct(
        public ?int $id,
        public int $experimentId,
        public string $mechanism,
        public string $operation,
        public int $httpStatus,
        public int $latencyMs,
        public int $authMs,
        public int $credentialSize,
        public int $roundTrips,
        public ?string $failedLayer,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
