<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lab\Application\Experiment\RecordEvent;
use Lab\Domain\Experiment\ExperimentRepository;
use Lab\Domain\Experiment\ExperimentStatus;
use Lab\Domain\Experiment\MetricRepository;
use Lab\Domain\Shared\TransactionManager;
use Symfony\Component\HttpFoundation\Response;

final class RecordCredentialRevocation
{
    public function __construct(
        private ExperimentRepository $experiments,
        private MetricRepository $metrics,
        private RecordEvent $events,
        private TransactionManager $transactions,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $mechanism): Response
    {
        $id = $request->header('X-Experiment-Id');
        if ($id === null) {
            return $next($request);
        }
        $experiment = ctype_digit($id) ? $this->experiments->findById((int) $id) : null;
        if ($experiment === null || $experiment->status() !== ExperimentStatus::RUNNING
            || $experiment->mechanism() !== $mechanism) {
            return response()->json(['error' => 'invalid_experiment'], 400);
        }

        return $this->transactions->transaction(fn (): Response => $this->revokeAndRecord($request, $next, $mechanism, (int) $id));
    }

    /** @param Closure(Request): Response $next */
    private function revokeAndRecord(Request $request, Closure $next, string $mechanism, int $id): Response
    {
        $response = $next($request);
        if ($response->getStatusCode() === 204) {
            // Exclude samples already stored, even when timestamps share the same second.
            $lastMetricId = 0;
            foreach ($this->metrics->listFor((int) $id) as $metric) {
                $lastMetricId = max($lastMetricId, $metric->id ?? 0);
            }
            $this->events->execute((int) $id, 'credential_revoked', [
                'mechanism' => $mechanism,
                'after_metric_id' => $lastMetricId,
            ]);
        }

        return $response;
    }
}
