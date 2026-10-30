<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lab\Application\Authentication\ApiKey\IssueApiKey;

trait ExercisesRevocationComparison
{
    private function exerciseRevocationComparison(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $email = "hu54-{$suffix}@lab.test";
        $password = bin2hex(random_bytes(16));
        $user = DB::table('users')->insertGetId(['name' => 'Prueba HU54', 'email' => $email, 'password' => Hash::make($password), 'is_active' => true]);
        $role = DB::table('roles')->where('name', 'ADMIN')->value('id');
        $this->assertNotNull($role);
        DB::table('role_user')->insert(['user_id' => $user, 'role_id' => $role]);
        $client = "hu54-{$suffix}";
        DB::table('oauth_clients')->insert(['name' => 'Prueba HU54', 'client_id' => $client, 'client_secret' => Hash::make($password)]);
        $admin = app(IssueApiKey::class)->execute((int) $user, 'Administrador de prueba');
        $target = app(IssueApiKey::class)->execute((int) $user, 'Credencial de prueba');
        $jwt = $this->postJson('/api/auth/jwt/login', ['email' => $email, 'password' => $password])->assertCreated()->json('token');
        $oauth = $this->postJson('/api/auth/oauth/token', ['grant_type' => 'password', 'client_id' => $client, 'client_secret' => $password, 'username' => $email, 'password' => $password])->assertOk()->json('access_token');
        $cases = [
            ['api_key', ['X-API-Key' => $target['plain']], '/api/auth/api-key/revoke/'.$target['apiKey']->id()->value(), [], ['X-API-Key' => $admin['plain']]],
            ['jwt', ['Authorization' => 'Bearer '.$jwt, 'X-Auth-Mechanism' => 'jwt'], '/api/auth/jwt/revoke', ['token' => $jwt], []],
            ['oauth', ['Authorization' => 'Bearer '.$oauth, 'X-Auth-Mechanism' => 'oauth'], '/api/auth/oauth/revoke', ['token' => $oauth], []],
        ];
        foreach ($cases as [$mechanism, $headers, $url, $body, $revokeHeaders]) {
            $id = $this->postJson('/api/experiments', ['name' => "HU54 {$suffix} {$mechanism}", 'mechanism' => $mechanism])->assertCreated()->json('id');
            $context = ['X-Experiment-Id' => (string) $id];
            $this->getJson('/api/tickets', array_merge($headers, $context))->assertOk();
            // Mismatched context must not revoke the credential.
            $this->postJson($url, $body, array_merge($revokeHeaders, ['X-Experiment-Id' => '999999999']))->assertStatus(400);
            $this->getJson('/api/tickets', array_merge($headers, $context))->assertOk();
            $this->postJson($url, $body, array_merge($revokeHeaders, $context))->assertNoContent();
            $events = $this->getJson("/api/experiments/{$id}/events")->assertOk()->json();
            $this->assertCount(1, $events);
            $this->assertSame('credential_revoked', $events[0]['event_type']);
            $this->assertSame($mechanism, $events[0]['payload']['mechanism']);
            $this->assertSame(['mechanism', 'after_metric_id'], array_keys($events[0]['payload']));
            $this->getJson('/api/tickets', array_merge($headers, $context))->assertUnauthorized();
            $metrics = $this->getJson("/api/experiments/{$id}/metrics")->assertOk()->assertJsonCount(3)->json();
            $this->assertSame(401, $metrics[2]['http_status']);
            $this->assertSame('none', $metrics[2]['mechanism']);
            $this->assertGreaterThan($events[0]['payload']['after_metric_id'], $metrics[2]['id']);
            $this->assertNotFalse(strtotime($metrics[2]['occurred_at']));
            $this->postJson("/api/experiments/{$id}/finish")->assertOk();
            $this->postJson($url, $body, array_merge($revokeHeaders, $context))->assertStatus(400);
        }
        $this->getJson('/api/tickets', ['Authorization' => 'Basic '.base64_encode($email.':'.$password)])->assertOk();
    }
}
