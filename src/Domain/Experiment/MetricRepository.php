<?php

declare(strict_types=1);

namespace Lab\Domain\Experiment;

/**
 * Puerto de métricas.
 */
interface MetricRepository
{
    public function record(Metric $metric): void;

    /**
     * @return list<Metric>
     */
    public function listFor(int $experimentId, ?string $mechanism = null, ?string $operation = null): array;
}
