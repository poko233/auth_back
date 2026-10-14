<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Lab\Application\Experiment\CompareMetrics;
use Lab\Domain\Experiment\MetricRepository;

/**
 * Lectura y comparación de métricas.
 */
final class MetricController
{
    public function __construct(
        private MetricRepository $metrics,
        private CompareMetrics $compare,
        private \Lab\Domain\Experiment\ExperimentRepository $experiments,
    ) {
    }

    public function index(string $experimentId): JsonResponse
    {
        $metrics = $this->metrics->listFor((int) $experimentId);

        return response()->json(array_map(
            static fn ($metric): array => [
                'mechanism' => $metric->mechanism,
                'operation' => $metric->operation,
                'http_status' => $metric->httpStatus,
                'latency_ms' => $metric->latencyMs,
                'auth_ms' => $metric->authMs,
                'credential_size' => $metric->credentialSize,
                'failed_layer' => $metric->failedLayer,
            ],
            $metrics,
        ));
    }

    public function compare(string $experimentId): JsonResponse
    {
        $losses = 0;

        foreach ($this->experiments->eventsFor((int) $experimentId) as $event) {
            if ($event->eventType === 'metric_loss') {
                $losses++;
            }
        }

        return response()->json([
            'rows' => $this->compare->execute((int) $experimentId),
            'losses' => $losses,
            'valid' => $losses === 0,
        ]);
    }
}
