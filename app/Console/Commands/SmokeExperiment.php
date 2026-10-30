<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Lab\Domain\Experiment\Experiment;
use Throwable;

/**
 * Smoke del experimento: misma secuencia con los 4 mecanismos.
 *
 * Secuencia: VIEWER → EDITOR → ADMIN → EDITOR → revocado, con las mismas
 * operaciones sobre los mismos recursos. Requiere php artisan serve activo.
 * Uso: php artisan lab:smoke-experiment api_key|basic|jwt|oauth
 */
final class SmokeExperiment extends Command
{
    protected $signature = 'lab:smoke-experiment
        {mechanism : api_key|basic|jwt|oauth}
        {--base-url=http://localhost:8000}
        {--admin-email=admin@lab.test}
        {--admin-password= : Contraseña del administrador para gestionar el experimento}';

    protected $description = 'Corre la secuencia experimental con un mecanismo y la compara.';

    private string $base = '';

    /** @var array<string, string> */
    private array $auth = [];

    /** @var array<string, string> */
    private array $managementAuth = [];

    private ?string $experimentId = null;

    private string $password = 'secreta123';

    public function handle(): int
    {
        $mechanism = (string) $this->argument('mechanism');
        $this->base = rtrim((string) $this->option('base-url'), '/').'/api';

        if (! in_array($mechanism, Experiment::MECHANISMS, true)) {
            $this->error('Mecanismo inválido. Usa api_key|basic|jwt|oauth.');

            return self::FAILURE;
        }

        $password = $this->option('admin-password') ?: $this->secret('Contraseña del administrador');
        $this->managementAuth = ['Authorization' => 'Basic '.base64_encode($this->option('admin-email').':'.$password)];

        $this->experimentId = null;

        try {
            $userId = $this->setupUser($mechanism);
            $this->authenticate($mechanism, $userId);
            $this->startExperiment($mechanism, $userId);
            $this->runScenario($mechanism, $userId);
            $this->finish();
            $this->compare();

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('Falló la ejecución del smoke; se conservan las métricas registradas.');

            if ($this->experimentId !== null) {
                try {
                    Http::withHeaders($this->managementAuth)
                        ->post("{$this->base}/experiments/{$this->experimentId}/finish", ['status' => 'FAILED'])->throw();
                } catch (Throwable) {
                    $this->warn('No se pudo confirmar el cierre. Consulte el estado del experimento.');
                }
            }

            return self::FAILURE;
        } finally {
            $this->auth = [];
            $this->managementAuth = [];
        }
    }

    private function runScenario(string $mechanism, int $userId): void
    {
        $stages = [
            ['VIEWER', ['VIEWER'], []],
            ['EDITOR', ['EDITOR'], ['VIEWER']],
            ['ADMIN', ['ADMIN'], ['EDITOR']],
            ['EDITOR', ['EDITOR'], ['ADMIN']],
        ];

        foreach ($stages as [$label, $add, $remove]) {
            $this->privileges($userId, $add, $remove);
            $this->operate($label);
        }

        $this->revokeCredential($mechanism, $userId);
        $this->operate('REVOCADO (debe fallar)');
    }

    private function setupUser(string $mechanism): int
    {
        $email = "smoke-{$mechanism}@lab.test";
        $existing = Http::get("{$this->base}/users")->json();
        $found = array_values(array_filter(
            is_array($existing) ? $existing : [],
            static fn (array $user): bool => ($user['email'] ?? null) === $email,
        ));

        if ($found !== []) {
            return (int) $found[0]['id'];
        }

        $created = Http::post("{$this->base}/users", [
            'name' => "Smoke {$mechanism}",
            'email' => $email,
            'password' => $this->password,
        ])->json();

        return (int) $created['id'];
    }

    private function startExperiment(string $mechanism, int $userId): void
    {
        $response = Http::withHeaders($this->managementAuth)->post("{$this->base}/experiments", [
            'name' => "Smoke {$mechanism} ".date('Y-m-d H:i:s'),
            'mechanism' => $mechanism,
            'test_user_id' => $userId,
            'resources' => ['tickets.list'],
        ])->throw()->json();

        $this->experimentId = (string) $response['id'];
        // /start solo cambia el estado: el smoke conserva SU secuencia de operaciones.
        Http::withHeaders($this->managementAuth)->post("{$this->base}/experiments/{$this->experimentId}/start")->throw();
        $this->info("Experimento #{$this->experimentId} ({$mechanism}).");
    }

    private function headers(): array
    {
        return array_merge($this->auth, ['X-Experiment-Id' => $this->experimentId]);
    }

    private function authenticate(string $mechanism, int $userId): void
    {
        $email = "smoke-{$mechanism}@lab.test";

        match ($mechanism) {
            'api_key' => $this->auth = ['X-API-Key' => $this->issueApiKey($userId)],
            'basic' => $this->auth = ['Authorization' => 'Basic '.base64_encode("{$email}:{$this->password}")],
            'jwt' => $this->auth = ['Authorization' => 'Bearer '.$this->loginJwt($email)],
            'oauth' => $this->auth = $this->oauthHeaders($email),
        };
    }

    private function issueApiKey(int $userId): string
    {
        $response = Http::withHeaders($this->managementAuth)->post("{$this->base}/auth/api-key/issue", [
            'user_id' => $userId, 'label' => 'Smoke experimental',
        ])->throw()->json();

        return (string) $response['plain'];
    }

    private function loginJwt(string $email): string
    {
        $response = Http::post("{$this->base}/auth/jwt/login", [
            'email' => $email, 'password' => $this->password,
        ])->json();

        return (string) $response['token'];
    }

    /**
     * @return array<string, string>
     */
    private function oauthHeaders(string $email): array
    {
        $response = Http::post("{$this->base}/auth/oauth/token", [
            'grant_type' => 'password',
            'client_id' => 'lab-client',
            'client_secret' => 'lab-secret',
            'username' => $email,
            'password' => $this->password,
        ])->json();

        return [
            'Authorization' => 'Bearer '.$response['access_token'],
            'X-Auth-Mechanism' => 'oauth',
        ];
    }

    /**
     * @param  list<string>  $add
     * @param  list<string>  $remove
     */
    private function privileges(int $userId, array $add, array $remove): void
    {
        Http::withHeaders($this->managementAuth)->post("{$this->base}/experiments/{$this->experimentId}/privileges", [
            'user_id' => $userId,
            'add_roles' => $add,
            'remove_roles' => $remove,
        ])->throw();
    }

    private function operate(string $stage): void
    {
        $http = Http::withHeaders($this->headers());
        $created = $http->post("{$this->base}/tickets", ['title' => "Smoke {$stage}"]);
        $read = $http->get("{$this->base}/tickets");

        $deleteStatus = '—';
        $ticketId = is_array($created->json()) ? ($created->json('id')) : null;

        if (is_int($ticketId)) {
            $deleteStatus = (string) $http->delete("{$this->base}/tickets/{$ticketId}")->status();
        }

        $this->line(sprintf(
            '%-22s POST=%d GET=%d DELETE=%s',
            $stage,
            $created->status(),
            $read->status(),
            $deleteStatus,
        ));
    }

    private function revokeCredential(string $mechanism, int $userId): void
    {
        if ($mechanism === 'api_key') {
            $keys = Http::withHeaders($this->managementAuth)->get("{$this->base}/auth/api-key/users/{$userId}/keys")->throw()->json();

            foreach (is_array($keys) ? $keys : [] as $key) {
                if ($key['revoked_at'] === null) {
                    Http::withHeaders($this->managementAuth)->post("{$this->base}/auth/api-key/revoke/{$key['id']}")->throw();
                }
            }
        }

        $this->privileges($userId, [], ['VIEWER', 'EDITOR', 'ADMIN']);
    }

    private function finish(): void
    {
        Http::withHeaders($this->managementAuth)->post("{$this->base}/experiments/{$this->experimentId}/finish")->throw();
    }

    private function compare(): void
    {
        $compare = Http::get("{$this->base}/experiments/{$this->experimentId}/metrics/compare")->json();

        $this->info('Comparación (mediana/p95, pérdidas='.($compare['losses'] ?? '?').'):');

        foreach (is_array($compare['rows'] ?? null) ? $compare['rows'] : [] as $row) {
            $this->line(sprintf(
                '  %-24s n=%d mediana=%.1fms p95=%.1fms std=%.1fms',
                $row['mechanism'].' '.$row['operation'],
                $row['n'],
                $row['median_ms'],
                $row['p95_ms'],
                $row['stddev_ms'],
            ));
        }
    }
}
