<?php

declare(strict_types=1);

use Lab\Application\Experiment\CompareMetrics;
use Lab\Domain\Experiment\Metric;
use Lab\Domain\Experiment\MetricRepository;
use Lab\Domain\Shared\Clock;

/**
 * Tests de CompareMetrics con fake (mediana/p95, nunca solo promedio).
 */
final class InMemoryMetricRepository implements MetricRepository
{
    /** @var list<Metric> */
    public array $metrics = [];

    public function record(Metric $metric): void
    {
        $this->metrics[] = $metric;
    }

    public function listFor(int $experimentId, ?string $mechanism = null, ?string $operation = null): array
    {
        return array_values(array_filter(
            $this->metrics,
            static fn (Metric $m): bool => $m->experimentId === $experimentId
                && ($mechanism === null || $m->mechanism === $mechanism)
                && ($operation === null || $m->operation === $operation),
        ));
    }
}

it('calcula mediana y p95 por mecanismo y operación', function (): void {
    $repo = new InMemoryMetricRepository();
    $compare = new CompareMetrics($repo);
    $now = new DateTimeImmutable();

    foreach ([100, 200, 300, 400, 500] as $ms) {
        $repo->record(new Metric(null, 1, 'api_key', 'GET tickets', 200, $ms, 5, 70, 1, null, $now));
    }

    $rows = $compare->execute(1);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['n'])->toBe(5)
        ->and($rows[0]['median_ms'])->toBe(300.0)
        ->and($rows[0]['mechanism'])->toBe('api_key');
});
