<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lab\Application\Experiment\RecordEvent;
use Lab\Domain\Credential\ApiKey\ApiKeyId;
use Lab\Domain\Credential\ApiKey\ApiKeyRepository;
use Lab\Domain\Credential\Jwt\Exception\JwtInvalid;
use Lab\Domain\Credential\Jwt\JwtSigner;
use Lab\Domain\Credential\OAuth\AccessTokenRepository;
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
        private ApiKeyRepository $apiKeys,
        private JwtSigner $jwt,
        private AccessTokenRepository $oauth,
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
        if ($experiment->testUserId() !== null
            && (string) $this->credentialUser($request, $mechanism) !== $experiment->testUserId()->value()) {
            return response()->json(['error' => 'experiment_credential_mismatch'], 422);
        }

        return $this->transactions->transaction(fn (): Response => $this->revokeAndRecord($request, $next, $mechanism, (int) $id));
    }

    private function credentialUser(Request $request, string $mechanism): ?int
    {
        if ($mechanism === 'api_key') {
            return $this->apiKeys->findById(ApiKeyId::fromInt((int) $request->route('id')))?->userId();
        }
        $token = $request->input('token');
        if (! is_string($token)) {
            return null;
        }
        if ($mechanism === 'oauth') {
            return $this->oauth->findByHash(hash('sha256', $token))?->userId();
        }
        try {
            return $this->jwt->parse($token)->sub;
        } catch (JwtInvalid) {
            return null;
        }
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
