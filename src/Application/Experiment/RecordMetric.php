<?php

declare(strict_types=1);

namespace Lab\Application\Experiment;

use Lab\Domain\Experiment\Metric;
use Lab\Domain\Experiment\MetricRepository;
use Lab\Domain\Shared\Clock;

/**
 * Registra una métrica de operación (la usa MetricsRecorderMiddleware).
 */
final class RecordMetric
{
    public function __construct(
        private MetricRepository $metrics,
        private Clock $clock,
    ) {
    }

    public function execute(
        int $experimentId,
        string $mechanism,
        string $operation,
        int $httpStatus,
        int $latencyMs,
        int $authMs,
        int $credentialSize,
        int $roundTrips = 1,
        ?string $failedLayer = null,
    ): void {
        $this->metrics->record(new Metric(
            null,
            $experimentId,
            $mechanism,
            $operation,
            $httpStatus,
            $latencyMs,
            $authMs,
            $credentialSize,
            $roundTrips,
            $failedLayer,
            $this->clock->now(),
        ));
    }
}
