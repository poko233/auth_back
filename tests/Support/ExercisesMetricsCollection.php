<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lab\Application\Authentication\ApiKey\IssueApiKey;

trait ExercisesMetricsCollection
{
    private function exerciseMetricsCollection(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $password = bin2hex(random_bytes(16));
        $users = [];
        foreach (['ADMIN', 'VIEWER'] as $role) {
            $email = 'hu52-'.$suffix.'-'.strtolower($role).'@lab.test';
            $id = DB::table('users')->insertGetId(['name' => 'Prueba HU52', 'email' => $email, 'password' => Hash::make($password), 'is_active' => true]);
            $roleId = DB::table('roles')->where('name', $role)->value('id');
            $this->assertNotNull($roleId);
            DB::table('role_user')->insert(['user_id' => $id, 'role_id' => $roleId]);
            $users[$role] = ['id' => $id, 'email' => $email, 'key' => app(IssueApiKey::class)->execute((int) $id, 'Prueba HU52')['plain']];
        }
        $subject = $users['VIEWER'];
        $management = ['X-API-Key' => $users['ADMIN']['key']];
        $jwt = $this->postJson('/api/auth/jwt/login', ['email' => $subject['email'], 'password' => $password])->assertCreated()->json('token');
        $client = 'hu52-'.$suffix;
        DB::table('oauth_clients')->insert(['name' => 'Prueba HU52', 'client_id' => $client, 'client_secret' => Hash::make($password)]);
        $oauth = $this->postJson('/api/auth/oauth/token', ['grant_type' => 'password', 'client_id' => $client, 'client_secret' => $password, 'username' => $subject['email'], 'password' => $password])->assertOk()->json('access_token');
        $mechanisms = [
            'api_key' => [['X-API-Key' => $subject['key']], ['X-API-Key' => 'unknown-credential']],
            'basic' => [['Authorization' => 'Basic '.base64_encode($subject['email'].':'.$password)], ['Authorization' => 'Basic '.base64_encode($subject['email'].':incorrect')]],
            'jwt' => [['Authorization' => 'Bearer '.$jwt, 'X-Auth-Mechanism' => 'jwt'], ['Authorization' => 'Bearer invalid.token.signature', 'X-Auth-Mechanism' => 'jwt']],
            'oauth' => [['Authorization' => 'Bearer '.$oauth, 'X-Auth-Mechanism' => 'oauth'], ['Authorization' => 'Bearer unknown-access-token', 'X-Auth-Mechanism' => 'oauth']],
        ];
        foreach ($mechanisms as $mechanism => [$valid, $invalid]) {
            $id = $this->postJson('/api/experiments', ['name' => "HU52 {$suffix} {$mechanism}", 'mechanism' => $mechanism, 'test_user_id' => (string) $subject['id'], 'resources' => ['tickets.list']], $management)->assertCreated()->json('id');
            $this->getJson("/api/experiments/{$id}/metrics/compare")->assertOk()->assertJsonPath('rows', []);
            $this->postJson("/api/experiments/{$id}/start", [], $management)->assertOk();
            $context = ['X-Experiment-Id' => (string) $id];
            $this->getJson('/api/tickets', [...$valid, ...$context])->assertOk();
            $this->getJson('/api/tickets', [...$valid, ...$context])->assertOk();
            $this->postJson('/api/tickets', ['title' => 'No debe crearse'], [...$valid, ...$context])->assertForbidden();
            $this->getJson('/api/tickets', [...$invalid, ...$context])->assertUnauthorized();
            $metrics = $this->getJson("/api/experiments/{$id}/metrics")->assertOk()->assertJsonCount(4)->json();
            $this->assertSame([200, 200, 403, 401], array_column($metrics, 'http_status'));
            foreach ($metrics as $metric) {
                $this->assertSame($mechanism, $metric['mechanism']);
                $this->assertNotFalse(strtotime($metric['occurred_at']));
                $this->assertGreaterThanOrEqual(0, $metric['latency_ms']);
                $this->assertGreaterThan(0, $metric['credential_size']);
                $this->assertArrayNotHasKey('credential', $metric);
            }
            $comparison = $this->getJson("/api/experiments/{$id}/metrics/compare")->assertOk()->assertJsonPath('losses', 0)->assertJsonPath('valid', true)->json('rows');
            $this->assertCount(2, $comparison);
            $get = array_values(array_filter($comparison, fn (array $row): bool => $row['operation'] === 'GET api/tickets'))[0];
            $this->assertSame(3, $get['n']);
            $this->assertGreaterThanOrEqual($get['median_ms'], $get['p95_ms']);
            $this->postJson("/api/experiments/{$id}/finish", [], $management)->assertOk();
            $this->getJson('/api/tickets', [...$valid, ...$context])->assertStatus(400);
            $this->getJson("/api/experiments/{$id}/metrics")->assertJsonCount(4);
        }
    }
}
