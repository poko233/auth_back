<?php

declare(strict_types=1);

namespace Lab\Application\Experiment;

use Lab\Domain\Experiment\MetricRepository;

/**
 * Compara métricas por mecanismo+operación: N, mediana, p95 y desviación.
 *
 * Nunca solo promedio: con baseline de red alto, la mediana y el p95 son los
 * estimadores que importan.
 */
final class CompareMetrics
{
    public function __construct(
        private MetricRepository $metrics,
    ) {
    }

    /**
     * @return list<array{mechanism: string, operation: string, n: int, median_ms: float, p95_ms: float, stddev_ms: float}>
     */
    public function execute(int $experimentId): array
    {
        $grouped = [];

        foreach ($this->metrics->listFor($experimentId) as $metric) {
            $grouped[$metric->mechanism.'|'.$metric->operation][] = $metric->latencyMs;
        }

        $rows = [];

        foreach ($grouped as $key => $latencies) {
            [$mechanism, $operation] = explode('|', $key, 2);
            sort($latencies);
            $n = count($latencies);
            $median = $this->percentile($latencies, 50);
            $mean = array_sum($latencies) / $n;
            $variance = array_sum(array_map(static fn (int $v): float => ($v - $mean) ** 2, $latencies)) / $n;

            $rows[] = [
                'mechanism' => $mechanism,
                'operation' => $operation,
                'n' => $n,
                'median_ms' => $median,
                'p95_ms' => $this->percentile($latencies, 95),
                'stddev_ms' => round(sqrt($variance), 2),
            ];
        }

        return $rows;
    }

    /**
     * @param list<int> $sorted
     */
    private function percentile(array $sorted, float $percent): float
    {
        $n = count($sorted);

        if ($n === 0) {
            return 0.0;
        }

        $rank = ($percent / 100) * ($n - 1);
        $low = (int) floor($rank);
        $high = (int) ceil($rank);

        if ($low === $high) {
            return (float) $sorted[$low];
        }

        return round($sorted[$low] + ($sorted[$high] - $sorted[$low]) * ($rank - $low), 2);
    }
}
