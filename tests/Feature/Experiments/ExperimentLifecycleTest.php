<?php

declare(strict_types=1);

namespace Tests\Feature\Experiments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Lab\Domain\Experiment\ExperimentRepository;
use Lab\Domain\Experiment\ExperimentStatus;
use Tests\Support\IssuesApiKeys;
use Tests\Support\ManagesExperiments;
use Tests\TestCase;

final class ExperimentLifecycleTest extends TestCase
{
    use IssuesApiKeys;
    use ManagesExperiments;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLabRbac();
    }

    public function test_crea_pendiente_con_usuario_recursos_y_fecha_y_lo_lista(): void
    {
        $subject = $this->makeUserWithKey('subject@lab.test', 'VIEWER');
        $payload = $this->experimentPayload('Comparación', 'basic', $subject['id']);
        $created = $this->postJson('/api/experiments', $payload, $this->managementHeaders())
            ->assertCreated()->assertJsonPath('status', 'PENDING')
            ->assertJsonPath('test_user_id', (string) $subject['id'])
            ->assertJsonPath('resources', ['tickets.list'])
            ->assertJsonPath('started_at', null)->assertJsonPath('finished_at', null);

        $this->assertNotNull($created->json('created_at'));
        $this->assertDatabaseCount('metrics', 0);
        $this->assertDatabaseHas('experiments', ['id' => $created->json('id'), 'test_user_id' => $subject['id'], 'status' => 'PENDING']);
        $this->getJson('/api/experiments', $this->managementHeaders())->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.id', $created->json('id'))->assertJsonPath('0.created_at', $created->json('created_at'));
    }

    public function test_exige_campos_y_rechaza_recursos_arbitrarios(): void
    {
        $this->postJson('/api/experiments', [], $this->managementHeaders())->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'El nombre del experimento es obligatorio')
            ->assertJsonPath('errors.mechanism.0', 'Debe seleccionar un mecanismo')
            ->assertJsonPath('errors.test_user_id.0', 'Debe seleccionar un usuario de prueba');

        $payload = $this->experimentPayload();
        $payload['resources'] = ['https://example.com'];
        $this->postJson('/api/experiments', $payload, $this->managementHeaders())->assertStatus(422)->assertJsonValidationErrors('resources.0');
        $payload['resources'] = [];
        $this->postJson('/api/experiments', $payload, $this->managementHeaders())->assertStatus(422)->assertJsonValidationErrors('resources');
        $payload['resources'] = ['tickets.list'];
        $payload['test_user_id'] = '999999';
        $this->postJson('/api/experiments', $payload, $this->managementHeaders())->assertStatus(422)->assertJsonValidationErrors('test_user_id');
        $payload = $this->experimentPayload();
        $payload['name'] = str_repeat('x', 256);
        $this->postJson('/api/experiments', $payload, $this->managementHeaders())->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_no_agrega_elegibilidad_no_requerida_al_crear(): void
    {
        $subject = $this->makeUserWithKey('inactive-subject@lab.test', 'VIEWER');
        DB::table('users')->where('id', $subject['id'])->update(['is_active' => false]);
        $this->postJson('/api/experiments', $this->experimentPayload('Usuario inactivo', 'basic', $subject['id']), $this->managementHeaders())->assertCreated();
    }

    public function test_inicio_por_id_no_ejecuta_solicitudes_y_no_admite_repeticion(): void
    {
        $id = $this->createRunningExperiment('Inicio');
        $this->assertDatabaseCount('experiments', 1);
        $this->assertDatabaseCount('metrics', 0);
        $started = DB::table('experiments')->where('id', $id)->value('started_at');
        $this->assertNotNull($started);
        $this->postJson("/api/experiments/{$id}/start", [], $this->managementHeaders())
            ->assertStatus(409)->assertJsonPath('error', 'experiment_running');
        $this->assertSame($started, DB::table('experiments')->where('id', $id)->value('started_at'));

        $this->postJson("/api/experiments/{$id}/finish", [], $this->managementHeaders())->assertOk()->assertJsonPath('status', 'COMPLETED');
        $finished = DB::table('experiments')->where('id', $id)->value('finished_at');
        $this->assertNotNull($finished);
        $this->postJson("/api/experiments/{$id}/start", [], $this->managementHeaders())
            ->assertStatus(409)->assertJsonPath('error', 'experiment_already_executed')->assertJsonPath('detail', 'Este experimento ya fue ejecutado');
        $this->postJson("/api/experiments/{$id}/finish", [], $this->managementHeaders())->assertStatus(409);
        $this->assertSame($finished, DB::table('experiments')->where('id', $id)->value('finished_at'));
    }

    public function test_actualizacion_condicional_rechaza_una_entidad_pendiente_obsoleta(): void
    {
        $id = (int) $this->postJson('/api/experiments', $this->experimentPayload(), $this->managementHeaders())->assertCreated()->json('id');
        $repository = app(ExperimentRepository::class);
        $stale = $repository->findById($id);
        $this->postJson("/api/experiments/{$id}/start", [], $this->managementHeaders())->assertOk();
        $stale->begin(new \DateTimeImmutable());
        $this->assertNull($repository->transition($stale, ExperimentStatus::PENDING));
    }

    public function test_no_finaliza_pendiente_y_conserva_metricas_al_fallar(): void
    {
        $id = (int) $this->postJson('/api/experiments', $this->experimentPayload(), $this->managementHeaders())->assertCreated()->json('id');
        $this->postJson("/api/experiments/{$id}/finish", [], $this->managementHeaders())->assertStatus(409);
        $this->postJson("/api/experiments/{$id}/start", [], $this->managementHeaders())->assertOk();
        $this->getJson('/api/tickets', ['X-API-Key' => 'invalid', 'X-Experiment-Id' => (string) $id])->assertUnauthorized();
        $this->postJson("/api/experiments/{$id}/finish", ['status' => 'FAILED'], $this->managementHeaders())->assertOk()->assertJsonPath('status', 'FAILED');
        $this->postJson("/api/experiments/{$id}/finish", [], $this->managementHeaders())->assertStatus(409);
        $this->postJson("/api/experiments/{$id}/start", [], $this->managementHeaders())->assertStatus(409);
        $this->assertDatabaseCount('metrics', 1);
    }

    public function test_historicos_conservan_nulos_y_estados(): void
    {
        DB::table('experiments')->insert(['name' => 'Histórico', 'mechanism' => 'jwt', 'status' => 'COMPLETED']);
        $this->getJson('/api/experiments', $this->managementHeaders())->assertOk()
            ->assertJsonPath('0.status', 'COMPLETED')->assertJsonPath('0.test_user_id', null)
            ->assertJsonPath('0.resources', null)->assertJsonPath('0.created_at', null)
            ->assertJsonPath('0.started_at', null)->assertJsonPath('0.finished_at', null);
    }

    public function test_exige_administrador_y_controla_id_inexistente(): void
    {
        $this->getJson('/api/experiments')->assertUnauthorized();
        $viewer = $this->makeUserWithKey('viewer-manager@lab.test', 'VIEWER');
        $this->postJson('/api/experiments', $this->experimentPayload(), $this->keyHeaders($viewer['key']))->assertForbidden();
        $this->postJson('/api/experiments/999999/start', [], $this->managementHeaders())->assertNotFound();
    }
}
