<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Lab\Domain\Experiment\Metric;
use Lab\Domain\Experiment\MetricRepository;
use Lab\Infrastructure\Persistence\Eloquent\Models\MetricModel;

/**
 * Implementación Eloquent del puerto de métricas.
 */
final class EloquentMetricRepository implements MetricRepository
{
    public function record(Metric $metric): void
    {
        $model = new MetricModel([
            'experiment_id' => $metric->experimentId,
            'mechanism' => $metric->mechanism,
            'operation' => $metric->operation,
            'http_status' => $metric->httpStatus,
            'latency_ms' => $metric->latencyMs,
            'auth_ms' => $metric->authMs,
            'credential_size' => $metric->credentialSize,
            'round_trips' => $metric->roundTrips,
            'failed_layer' => $metric->failedLayer,
            'occurred_at' => $metric->occurredAt,
        ]);
        $model->save();
    }

    public function listFor(int $experimentId, ?string $mechanism = null, ?string $operation = null): array
    {
        $query = MetricModel::query()
            ->select(['id', 'experiment_id', 'mechanism', 'operation', 'http_status', 'latency_ms', 'auth_ms', 'credential_size', 'round_trips', 'failed_layer', 'occurred_at'])
            ->where('experiment_id', $experimentId)
            ->orderBy('id');

        if ($mechanism !== null) {
            $query->where('mechanism', $mechanism);
        }

        if ($operation !== null) {
            $query->where('operation', $operation);
        }

        $metrics = [];

        foreach ($query->get() as $model) {
            $metrics[] = new Metric(
                (int) $model->getAttribute('id'),
                (int) $model->getAttribute('experiment_id'),
                (string) $model->getAttribute('mechanism'),
                (string) $model->getAttribute('operation'),
                (int) $model->getAttribute('http_status'),
                (int) $model->getAttribute('latency_ms'),
                (int) $model->getAttribute('auth_ms'),
                (int) $model->getAttribute('credential_size'),
                (int) $model->getAttribute('round_trips'),
                $model->getAttribute('failed_layer') !== null ? (string) $model->getAttribute('failed_layer') : null,
                $this->toImmutable($model->getAttribute('occurred_at')),
            );
        }

        return $metrics;
    }

    private function toImmutable(mixed $value): DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}
