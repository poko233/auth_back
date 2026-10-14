<?php

declare(strict_types=1);

namespace Tests\Feature\Experiments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

/**
 * Ciclo de vida de experimentos + privilegios del escenario end-to-end.
 */
final class ExperimentTest extends TestCase
{
    use IssuesApiKeys;
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

        $experimentId = (int) $this->postJson('/api/experiments', [
            'name' => 'Escalado VIEWER→EDITOR',
            'mechanism' => 'api_key',
        ])->assertCreated()->assertJsonPath('status', 'RUNNING')->json('id');

        $this->postJson("/api/experiments/{$experimentId}/privileges", [
            'user_id' => $userId,
            'add_roles' => ['VIEWER'],
        ])->assertNoContent();

        $this->postJson("/api/experiments/{$experimentId}/privileges", [
            'user_id' => $userId,
            'add_roles' => ['EDITOR'],
            'remove_roles' => ['VIEWER'],
        ])->assertNoContent();

        $this->getJson("/api/experiments/{$experimentId}/events")
            ->assertOk()->assertJsonCount(2)->assertJsonFragment(['event_type' => 'role_change']);

        $this->postJson("/api/experiments/{$experimentId}/finish")
            ->assertOk()->assertJsonPath('status', 'COMPLETED');
    }

    public function test_mecanismo_invalido_rechazado(): void
    {
        $this->postJson('/api/experiments', ['name' => 'X', 'mechanism' => 'saml'])->assertStatus(422);
    }
}
