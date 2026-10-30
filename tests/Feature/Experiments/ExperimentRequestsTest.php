<?php

declare(strict_types=1);

namespace Tests\Feature\Experiments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lab\Application\Authentication\Jwt\IssueJwt;
use Lab\Application\Authentication\OAuth\IssueTokenByPassword;
use Lab\Domain\Credential\OAuth\AccessToken;
use Lab\Domain\Credential\OAuth\AccessTokenRepository;
use Lab\Domain\Experiment\Metric;
use Lab\Domain\Experiment\MetricRepository;
use Tests\Support\IssuesApiKeys;
use Tests\Support\ManagesExperiments;
use Tests\TestCase;

final class ExperimentRequestsTest extends TestCase
{
    use IssuesApiKeys;
    use ManagesExperiments;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLabRbac();
    }

    public function test_dos_solicitudes_reales_por_mecanismo_conservan_identidad_y_401(): void
    {
        $subject = $this->makeUserWithKey('subject@lab.test', 'VIEWER');
        $jwt = app(IssueJwt::class)->execute($subject['id'])['token'];
        $oauth = str_repeat('a', 64);
        $clientId = DB::table('oauth_clients')->insertGetId(['client_id' => 'experiment-fixture', 'client_secret' => Hash::make('fixture'), 'name' => 'Fixture']);
        app(AccessTokenRepository::class)->save(AccessToken::create(IssueTokenByPassword::hash($oauth), $clientId, $subject['id'], [], new \DateTimeImmutable('+1 hour')));
        $pairs = [
            'basic' => [['Authorization' => 'Basic '.base64_encode('subject@lab.test:secreta123')], ['Authorization' => 'Basic '.base64_encode('subject@lab.test:incorrecta')]],
            'api_key' => [$this->keyHeaders($subject['key']), $this->keyHeaders($subject['key'].'.invalid')],
            'jwt' => [['Authorization' => 'Bearer '.$jwt, 'X-Auth-Mechanism' => 'jwt'], ['Authorization' => 'Bearer invalid.signature.token', 'X-Auth-Mechanism' => 'jwt']],
            'oauth' => [['Authorization' => 'Bearer '.$oauth, 'X-Auth-Mechanism' => 'oauth'], ['Authorization' => 'Bearer '.$oauth.'.invalid', 'X-Auth-Mechanism' => 'oauth']],
        ];

        foreach ($pairs as $mechanism => [$valid, $invalid]) {
            $id = $this->createRunningExperiment($mechanism, $mechanism, $subject['id']);
            $this->getJson('/api/users/me', $valid)->assertOk()->assertJsonPath('user.id', (string) $subject['id']);
            $this->getJson('/api/tickets', [...$valid, 'X-Experiment-Id' => (string) $id])->assertOk();
            $this->getJson('/api/tickets', [...$invalid, 'X-Experiment-Id' => (string) $id])->assertUnauthorized();
            $this->getJson("/api/experiments/{$id}/metrics")->assertOk()->assertJsonCount(2)
                ->assertJsonPath('0.mechanism', $mechanism)->assertJsonPath('1.mechanism', $mechanism)->assertJsonPath('1.http_status', 401);
            $this->postJson("/api/experiments/{$id}/finish", [], $this->managementHeaders())->assertOk();
        }

        $this->assertDatabaseCount('metrics', 8);
    }

    public function test_sin_evidencia_conserva_none_y_la_precedencia_no_cambia(): void
    {
        $id = $this->createRunningExperiment('Sin credencial');
        $this->getJson('/api/tickets', ['X-Experiment-Id' => (string) $id])->assertUnauthorized();
        $this->assertDatabaseHas('metrics', ['experiment_id' => $id, 'mechanism' => 'none', 'http_status' => 401]);
        $this->getJson('/api/tickets', [
            'X-Experiment-Id' => (string) $id,
            'X-API-Key' => 'invalid',
            'Authorization' => 'Basic '.base64_encode('experiment-manager@lab.test:secreta123'),
        ])->assertUnauthorized();
        $this->getJson("/api/experiments/{$id}/metrics")->assertOk()->assertJsonPath('1.mechanism', 'api_key');
    }

    public function test_perdida_de_metrica_no_inventa_filas_y_se_informa_en_compare(): void
    {
        $id = $this->createRunningExperiment('Pérdida');
        $this->app->bind(MetricRepository::class, fn () => new class implements MetricRepository
        {
            public function record(Metric $metric): void
            {
                throw new \RuntimeException('Fallo de persistencia de fixture');
            }

            public function listFor(int $experimentId, ?string $mechanism = null, ?string $operation = null): array
            {
                return [];
            }
        });
        $this->getJson('/api/tickets', ['X-API-Key' => 'invalid', 'X-Experiment-Id' => (string) $id])->assertUnauthorized();
        $this->assertDatabaseCount('metrics', 0);
        $this->getJson("/api/experiments/{$id}/metrics/compare")->assertOk()->assertJsonPath('losses', 1)->assertJsonPath('valid', false)->assertJsonPath('rows', []);
    }
}
