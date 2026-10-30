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
        $id = $this->postJson('/api/experiments', ['name' => 'Rollback', 'mechanism' => 'api_key'])->assertCreated()->json('id');
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
}
