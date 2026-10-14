<?php

declare(strict_types=1);

namespace Tests\Feature\Experiments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

/**
 * Métricas por request + comparación end-to-end.
 */
final class MetricsTest extends TestCase
{
    use IssuesApiKeys;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLabRbac();
    }

    public function test_middleware_registra_metrica_con_layer_y_compara(): void
    {
        $viewer = $this->makeUserWithKey('viewer@lab.test', 'VIEWER');
        $admin = $this->makeUserWithKey('admin@lab.test', 'ADMIN');

        $experimentId = (int) $this->postJson('/api/experiments', [
            'name' => 'Comparación',
            'mechanism' => 'api_key',
        ])->assertCreated()->json('id');

        $headers = fn (string $key): array => array_merge(
            $this->keyHeaders($key),
            ['X-Experiment-Id' => (string) $experimentId],
        );

        // 3 lecturas OK + 1 denegada por RBAC.
        $this->getJson('/api/tickets', $headers($admin['key']))->assertOk();
        $this->getJson('/api/tickets', $headers($admin['key']))->assertOk();
        $this->getJson('/api/tickets', $headers($admin['key']))->assertOk();

        $ticketId = $this->postJson('/api/tickets', ['title' => 'Falla'], $headers($admin['key']))
            ->assertCreated()->json('id');

        $this->deleteJson("/api/tickets/{$ticketId}", [], $headers($viewer['key']))
            ->assertForbidden();

        // Sin experimento no se registra nada.
        $this->assertDatabaseCount('metrics', 5);

        $this->getJson("/api/experiments/{$experimentId}/metrics")
            ->assertOk()->assertJsonCount(5);

        $compare = $this->getJson("/api/experiments/{$experimentId}/metrics/compare")
            ->assertOk()->assertJsonPath('losses', 0)->assertJsonPath('valid', true)->json('rows');

        $this->assertNotEmpty($compare);

        $getRow = array_values(array_filter(
            $compare,
            static fn (array $row): bool => $row['operation'] === 'GET api/tickets',
        ))[0];

        $this->assertSame(3, $getRow['n']);
        $this->assertArrayHasKey('median_ms', $getRow);
        $this->assertArrayHasKey('p95_ms', $getRow);
        $this->assertArrayHasKey('stddev_ms', $getRow);
    }

    public function test_401_por_key_invalida_queda_registrado(): void
    {
        $experimentId = (int) $this->postJson('/api/experiments', [
            'name' => 'Fallos',
            'mechanism' => 'api_key',
        ])->assertCreated()->json('id');

        $this->getJson('/api/tickets', [
            'X-API-Key' => str_repeat('0', 64),
            'X-Experiment-Id' => (string) $experimentId,
        ])->assertUnauthorized();

        $this->assertDatabaseHas('metrics', [
            'experiment_id' => $experimentId,
            'http_status' => 401,
            'mechanism' => 'none',
        ]);
    }

    public function test_experimento_invalido_o_cerrado_responde_400(): void
    {
        $this->getJson('/api/tickets', ['X-Experiment-Id' => '999999'])
            ->assertStatus(400)->assertJsonPath('error', 'invalid_experiment');

        $experimentId = (int) $this->postJson('/api/experiments', [
            'name' => 'Cerrado',
            'mechanism' => 'api_key',
        ])->assertCreated()->json('id');

        $this->postJson("/api/experiments/{$experimentId}/finish")->assertOk();

        $this->getJson('/api/tickets', ['X-Experiment-Id' => (string) $experimentId])
            ->assertStatus(400)->assertJsonPath('error', 'invalid_experiment');
    }

    public function test_metricas_no_cruzan_entre_experimentos(): void
    {
        $admin = $this->makeUserWithKey('admin@lab.test', 'ADMIN');

        $first = (int) $this->postJson('/api/experiments', [
            'name' => 'Uno', 'mechanism' => 'api_key',
        ])->assertCreated()->json('id');

        $second = (int) $this->postJson('/api/experiments', [
            'name' => 'Dos', 'mechanism' => 'api_key',
        ])->assertCreated()->json('id');

        $headers = fn (int $exp): array => array_merge(
            $this->keyHeaders($admin['key']),
            ['X-Experiment-Id' => (string) $exp],
        );

        $this->getJson('/api/tickets', $headers($first))->assertOk();
        $this->getJson('/api/tickets', $headers($first))->assertOk();
        $this->getJson('/api/tickets', $headers($second))->assertOk();

        $this->assertDatabaseCount('metrics', 3);

        $firstRows = $this->getJson("/api/experiments/{$first}/metrics")->assertOk()->json();
        $secondRows = $this->getJson("/api/experiments/{$second}/metrics")->assertOk()->json();

        $this->assertCount(2, $firstRows);
        $this->assertCount(1, $secondRows);
    }

    public function test_compare_sin_metricas_no_tiene_filas(): void
    {
        $compare = app(\Lab\Application\Experiment\CompareMetrics::class);

        // Sin métricas no hay filas.
        $this->assertSame([], $compare->execute(999));
    }
}
