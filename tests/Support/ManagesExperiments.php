<?php

declare(strict_types=1);

namespace Tests\Support;

trait ManagesExperiments
{
    /** @var array{id: int, key: string}|null */
    private ?array $experimentAdministrator = null;

    /** @return array<string, string> */
    protected function managementHeaders(): array
    {
        $this->experimentAdministrator ??= $this->makeUserWithKey('experiment-manager@lab.test', 'ADMIN');

        return $this->keyHeaders($this->experimentAdministrator['key']);
    }

    /** @return array{name: string, mechanism: string, test_user_id: string, resources: list<string>} */
    protected function experimentPayload(string $name = 'Experimento', string $mechanism = 'api_key', ?int $testUserId = null): array
    {
        $this->managementHeaders();

        return [
            'name' => $name,
            'mechanism' => $mechanism,
            'test_user_id' => (string) ($testUserId ?? $this->experimentAdministrator['id']),
            'resources' => ['tickets.list'],
        ];
    }

    protected function createRunningExperiment(string $name, string $mechanism = 'api_key', ?int $testUserId = null): int
    {
        $id = (int) $this->postJson('/api/experiments', $this->experimentPayload($name, $mechanism, $testUserId), $this->managementHeaders())
            ->assertCreated()->assertJsonPath('status', 'PENDING')->json('id');

        $this->postJson("/api/experiments/{$id}/start", [], $this->managementHeaders())
            ->assertOk()->assertJsonPath('id', $id)->assertJsonPath('status', 'RUNNING');

        return $id;
    }
}
