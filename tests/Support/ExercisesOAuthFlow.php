<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

trait ExercisesOAuthFlow
{
    /** @return array<string, string> */
    private function oauthFixture(): array
    {
        $suffix = bin2hex(random_bytes(6));
        $email = "hu36-37-{$suffix}@lab.test";
        $password = bin2hex(random_bytes(16));
        $client = "hu36-37-{$suffix}";
        $userId = DB::table('users')->insertGetId(['name' => 'Prueba HU36-37', 'email' => $email, 'password' => Hash::make($password), 'is_active' => true]);
        $roleId = DB::table('roles')->where('name', 'VIEWER')->value('id');
        $this->assertNotNull($roleId, 'Se necesita el rol VIEWER del laboratorio.');
        DB::table('role_user')->insert(['user_id' => $userId, 'role_id' => $roleId]);
        DB::table('oauth_clients')->insert(['client_id' => $client, 'client_secret' => Hash::make($password), 'name' => 'Prueba HU36-37']);

        return ['grant_type' => 'password', 'client_id' => $client, 'client_secret' => $password, 'username' => $email, 'password' => $password];
    }

    /** @return array<string, string> */
    private function oauthHeadersFor(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token, 'X-Auth-Mechanism' => 'oauth'];
    }

    private function exerciseOAuthFlow(): void
    {
        $credentials = $this->oauthFixture();
        $original = $this->postJson('/api/auth/oauth/token', $credentials)->assertOk()->json();
        $this->getJson('/api/tickets', $this->oauthHeadersFor($original['access_token']))->assertOk();
        $this->getJson('/api/tickets', ['Authorization' => 'Bearer '.$original['access_token']])->assertUnauthorized();
        $this->getJson('/api/tickets', $this->oauthHeadersFor('token-desconocido'))->assertUnauthorized();
        DB::table('oauth_access_tokens')->where('access_token', hash('sha256', $original['access_token']))->update(['expires_at' => now()->subMinute()]);
        $this->getJson('/api/tickets', $this->oauthHeadersFor($original['access_token']))->assertUnauthorized();
        $renewed = $this->postJson('/api/auth/oauth/refresh', ['refresh_token' => $original['refresh_token']])
            ->assertOk()->assertJsonStructure(['token_type', 'access_token', 'refresh_token', 'expires_in'])->json();
        $this->assertNotSame($original['access_token'], $renewed['access_token']);
        $this->assertNotSame($original['refresh_token'], $renewed['refresh_token']);
        $this->getJson('/api/tickets', $this->oauthHeadersFor($renewed['access_token']))->assertOk();
        $this->getJson('/api/tickets', $this->oauthHeadersFor($original['access_token']))->assertUnauthorized();
        $this->assertDatabaseHas('oauth_access_tokens', ['access_token' => hash('sha256', $renewed['access_token'])]);
        $this->assertDatabaseHas('oauth_refresh_tokens', ['refresh_token' => hash('sha256', $original['refresh_token']), 'replaced_by' => hash('sha256', $renewed['refresh_token'])]);
        $this->postJson('/api/auth/oauth/refresh', ['refresh_token' => $original['refresh_token']])
            ->assertStatus(400)->assertJsonPath('error', 'invalid_grant')->assertJsonPath('reason', 'revoked_token');
        $this->getJson('/api/tickets', $this->oauthHeadersFor($renewed['access_token']))->assertUnauthorized();
        $active = $this->postJson('/api/auth/oauth/token', $credentials)->assertOk()->json();
        $replacement = $this->postJson('/api/auth/oauth/refresh', ['refresh_token' => $active['refresh_token']])->assertOk()->json();
        $this->getJson('/api/tickets', $this->oauthHeadersFor($active['access_token']))->assertUnauthorized();
        $this->getJson('/api/tickets', $this->oauthHeadersFor($replacement['access_token']))->assertOk();
        $expired = $this->postJson('/api/auth/oauth/token', $credentials)->assertOk()->json();
        DB::table('oauth_refresh_tokens')->where('refresh_token', hash('sha256', $expired['refresh_token']))->update(['expires_at' => now()->subMinute()]);
        $this->postJson('/api/auth/oauth/refresh', ['refresh_token' => $expired['refresh_token']])
            ->assertStatus(400)->assertJsonPath('reason', 'expired_token')->assertJsonPath('detail', 'El token de renovación ha expirado');
        $revoked = $this->postJson('/api/auth/oauth/token', $credentials)->assertOk()->json();
        $this->postJson('/api/auth/oauth/revoke', ['token' => $revoked['access_token']])->assertNoContent();
        $this->postJson('/api/auth/oauth/refresh', ['refresh_token' => $revoked['refresh_token']])
            ->assertStatus(400)->assertJsonPath('reason', 'revoked_token')->assertJsonPath('detail', 'El token de renovación ha sido revocado');
        $this->getJson('/api/tickets', $this->oauthHeadersFor($revoked['access_token']))->assertUnauthorized();
        $this->postJson('/api/auth/oauth/refresh', ['refresh_token' => str_repeat('x', 64)])
            ->assertStatus(400)->assertExactJson(['error' => 'invalid_grant']);
    }
}
