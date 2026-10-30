<?php

declare(strict_types=1);

namespace Tests\Feature\Experiments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\IssuesApiKeys;
use Tests\Support\ManagesExperiments;
use Tests\TestCase;

/**
 * Ciclo de vida de experimentos + privilegios del escenario end-to-end.
 */
final class ExperimentTest extends TestCase
{
    use IssuesApiKeys;
    use ManagesExperiments;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLabRbac();
    }

    public function test_ciclo_start_privileges_finish_con_eventos(): void
    {
        $userId = (int) $this->postJson('/api/users', [
            'name' => 'Sujeto', 'email' => 'sujeto@lab.test', 'password' => 'secreta123',
        ])->assertCreated()->json('id');

        $experimentId = $this->createRunningExperiment('Escalado VIEWER→EDITOR', 'api_key', $userId);

        $this->postJson("/api/experiments/{$experimentId}/privileges", [
            'user_id' => $userId,
            'add_roles' => ['VIEWER'],
        ], $this->managementHeaders())->assertNoContent();

        $this->postJson("/api/experiments/{$experimentId}/privileges", [
            'user_id' => $userId,
            'add_roles' => ['EDITOR'],
            'remove_roles' => ['VIEWER'],
        ], $this->managementHeaders())->assertNoContent();

        $this->getJson("/api/experiments/{$experimentId}/events", $this->managementHeaders())
            ->assertOk()->assertJsonCount(2)->assertJsonFragment(['event_type' => 'role_change']);

        $this->postJson("/api/experiments/{$experimentId}/finish", [], $this->managementHeaders())
            ->assertOk()->assertJsonPath('status', 'COMPLETED');
    }

    public function test_mecanismo_invalido_rechazado(): void
    {
        $payload = $this->experimentPayload();
        $payload['mechanism'] = 'saml';
        $this->postJson('/api/experiments', $payload, $this->managementHeaders())->assertStatus(422);
    }
}
