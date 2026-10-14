<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lab\Application\Authentication\AuthenticatedPrincipal;
use Lab\Application\Experiment\RecordEvent;
use Lab\Application\Experiment\RecordMetric;
use Lab\Domain\Experiment\ExperimentRepository;
use Lab\Domain\Experiment\ExperimentStatus;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Registra métricas por request cuando hay experimento RUNNING.
 *
 * Opción B (laboratorio): best-effort con contador de pérdidas. Si el INSERT
 * falla, se intenta un evento 'metric_loss'; si eso falla, Log. El compare
 * expone losses: con pérdidas > 0 la comparación no es válida y hay que
 * reportarlo. N útil + tasa de pérdida van en el informe.
 *
 * Semántica (idéntica para los 4 mecanismos):
 * - auth_ms: solo las llamadas authenticate del flexible (lookup/hash/
 *   cripto+revocación). NUNCA incluye el INSERT de la métrica.
 * - latency_ms: request completo (auth + autorización + negocio + persistencia).
 * - mechanism: del principal (nunca de un header); 'none' si no hubo auth.
 * - failed_layer: parseado del body cuando trae 'layer' (403 de RBAC/regla).
 *
 * El middleware es EXTERNO al de auth: los 401 también se registran.
 * X-Experiment-Id inválido o no-RUNNING → 400 (sin contaminación cruzada).
 */
final class MetricsRecorderMiddleware
{
    public function __construct(
        private RecordMetric $record,
        private RecordEvent $events,
        private ExperimentRepository $experiments,
    ) {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $experimentId = $request->header('X-Experiment-Id');

        if ($experimentId === null) {
            return $next($request);
        }

        if (! ctype_digit($experimentId) || ! $this->isRunning((int) $experimentId)) {
            return response()->json(['error' => 'invalid_experiment'], 400);
        }

        $start = microtime(true);
        $response = $next($request);
        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        $this->persist((int) $experimentId, $request, $response, $latencyMs);

        return $response;
    }

    private function isRunning(int $experimentId): bool
    {
        try {
            $experiment = $this->experiments->findById($experimentId);
        } catch (Throwable) {
            return false;
        }

        return $experiment !== null && $experiment->status() === ExperimentStatus::RUNNING;
    }

    private function persist(int $experimentId, Request $request, Response $response, int $latencyMs): void
    {
        try {
            $principal = $request->attributes->get(ApiKeyAuthMiddleware::ATTRIBUTE);
            $mechanism = $principal instanceof AuthenticatedPrincipal ? $principal->mechanism : 'none';

            $credential = $request->header('X-API-Key', $request->header('Authorization', ''));

            $failedLayer = null;
            $content = $response->getContent();

            if (is_string($content)) {
                $decoded = json_decode($content, true);
                $failedLayer = is_array($decoded) && isset($decoded['layer']) ? (string) $decoded['layer'] : null;
            }

            $this->record->execute(
                $experimentId,
                $mechanism,
                $request->getMethod().' '.$request->path(),
                $response->getStatusCode(),
                $latencyMs,
                (int) $request->attributes->get('auth.ms', 0),
                strlen($credential),
                1,
                $failedLayer,
            );
        } catch (Throwable $e) {
            try {
                $this->events->execute($experimentId, 'metric_loss', ['error' => $e->getMessage()]);
            } catch (Throwable) {
                Log::warning('Pérdida de métrica sin registro.', ['experiment_id' => $experimentId]);
            }
        }
    }
}
