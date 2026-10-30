<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lab\Application\Authentication\ApiKey\IssueApiKey;
use Lab\Domain\Experiment\ExperimentRepository;
use Tests\Support\ExercisesRevocationComparison;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

final class CredentialRevocationExperimentTest extends TestCase
{
    use ExercisesRevocationComparison;
    use IssuesApiKeys;
    use RefreshDatabase;

    public function test_revocation_evidence_for_all_token_mechanisms(): void
    {
        $this->seedLabRbac();
        $this->exerciseRevocationComparison();
    }

    public function test_failed_event_persistence_rolls_back_revocation(): void
    {
        $this->seedLabRbac();
        $admin = $this->makeUserWithKey('rollback@lab.test', 'ADMIN');
        $target = app(IssueApiKey::class)->execute($admin['id'], 'Rollback');
        $headers = ['X-API-Key' => $admin['key']];
        $id = $this->postJson('/api/experiments', ['name' => 'Rollback', 'mechanism' => 'api_key', 'test_user_id' => (string) $admin['id'], 'resources' => ['tickets.list']], $headers)->assertCreated()->json('id');
        $this->postJson("/api/experiments/{$id}/start", [], $headers)->assertOk();
        $repository = app(ExperimentRepository::class);
        $mock = \Mockery::mock(ExperimentRepository::class);
        $mock->shouldReceive('findById')->andReturnUsing($repository->findById(...));
        $mock->shouldReceive('recordEvent')->andThrow(new \RuntimeException('Event persistence failed'));
        $this->app->instance(ExperimentRepository::class, $mock);
        $this->withoutExceptionHandling();
        try {
            $this->postJson('/api/auth/api-key/revoke/'.$target['apiKey']->id()->value(), [], [
                'X-API-Key' => $admin['key'], 'X-Experiment-Id' => (string) $id,
            ]);
            $this->fail('Expected event persistence failure.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Event persistence failed', $error->getMessage());
        }
        $this->assertDatabaseHas('api_keys', ['id' => $target['apiKey']->id()->value(), 'revoked_at' => null]);
        $this->getJson('/api/tickets', ['X-API-Key' => $target['plain']])->assertOk();
    }

    public function test_revocation_rejects_a_credential_from_another_test_user(): void
    {
        $this->seedLabRbac();
        $admin = $this->makeUserWithKey('manager@lab.test', 'ADMIN');
        $other = $this->makeUserWithKey('other@lab.test', 'VIEWER');
        $headers = ['X-API-Key' => $admin['key']];
        $id = $this->postJson('/api/experiments', ['name' => 'Usuario aislado', 'mechanism' => 'api_key', 'test_user_id' => (string) $admin['id'], 'resources' => ['tickets.list']], $headers)->assertCreated()->json('id');
        $this->postJson("/api/experiments/{$id}/start", [], $headers)->assertOk();
        $keyId = $this->postJson('/api/auth/api-key/login', ['key' => $other['key']])->assertOk()->json('key_id');
        $this->postJson('/api/auth/api-key/revoke/'.$keyId, [], [...$headers, 'X-Experiment-Id' => (string) $id])->assertStatus(422)->assertJsonPath('error', 'experiment_credential_mismatch');
        $this->getJson('/api/tickets', ['X-API-Key' => $other['key']])->assertOk();
        $this->getJson("/api/experiments/{$id}/events", $headers)->assertOk()->assertJsonCount(0);
    }

    public function test_role_change_keeps_the_metric_boundary_and_does_not_revoke_credentials(): void
    {
        $this->seedLabRbac();
        $admin = $this->makeUserWithKey('manager-role@lab.test', 'ADMIN');
        $subject = $this->makeUserWithKey('subject-role@lab.test', 'VIEWER');
        $headers = ['X-API-Key' => $admin['key']];
        $id = $this->postJson('/api/experiments', ['name' => 'Cambio de privilegios', 'mechanism' => 'api_key', 'test_user_id' => (string) $subject['id'], 'resources' => ['tickets.list']], $headers)->assertCreated()->json('id');
        $this->postJson("/api/experiments/{$id}/start", [], $headers)->assertOk();
        $testHeaders = ['X-API-Key' => $subject['key'], 'X-Experiment-Id' => (string) $id];
        $this->getJson('/api/tickets', $testHeaders)->assertOk();
        $before = $this->getJson("/api/experiments/{$id}/metrics")->assertOk()->json('0.id');
        $this->postJson("/api/experiments/{$id}/privileges", ['user_id' => $subject['id'], 'add_roles' => ['rol-inexistente']], $headers)->assertStatus(422);
        $this->postJson("/api/experiments/{$id}/privileges", ['user_id' => $subject['id'], 'remove_roles' => ['VIEWER']], $headers)->assertNoContent();
        $this->getJson('/api/tickets', $testHeaders)->assertForbidden()->assertJsonPath('layer', 'rbac');
        $this->getJson("/api/experiments/{$id}/events", $headers)->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.event_type', 'role_change')->assertJsonPath('0.payload.after_metric_id', $before);
    }
}
