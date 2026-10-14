<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

/**
 * Servidor OAuth propio (ROPC) end-to-end + X-Auth-Mechanism.
 */
final class OAuthTest extends TestCase
{
    use IssuesApiKeys;
    use RefreshDatabase;

    private const CLIENT = 'lab-client';

    private const SECRET = 'lab-secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLabRbac();

        DB::table('oauth_clients')->insert([
            'client_id' => self::CLIENT,
            'client_secret' => Hash::make(self::SECRET),
            'name' => 'Lab',
        ]);

        $adminId = (int) $this->postJson('/api/users', [
            'name' => 'Admin', 'email' => 'admin@lab.test', 'password' => 'secreta123',
        ])->assertCreated()->json('id');
        $this->postJson("/api/users/{$adminId}/roles", ['role' => 'ADMIN'])->assertNoContent();

        $viewerId = (int) $this->postJson('/api/users', [
            'name' => 'Viewer', 'email' => 'viewer@lab.test', 'password' => 'secreta123',
        ])->assertCreated()->json('id');
        $this->postJson("/api/users/{$viewerId}/roles", ['role' => 'VIEWER'])->assertNoContent();
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    private function passwordGrant(string $email, string $password): array
    {
        $response = $this->postJson('/api/auth/oauth/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT,
            'client_secret' => self::SECRET,
            'username' => $email,
            'password' => $password,
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->json();

        return ['access_token' => $response['access_token'], 'refresh_token' => $response['refresh_token']];
    }

    private function oauthHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'X-Auth-Mechanism' => 'oauth',
        ];
    }

    public function test_password_grant_emite_par_y_opera(): void
    {
        $tokens = $this->passwordGrant('admin@lab.test', 'secreta123');

        $this->postJson('/api/tickets', ['title' => 'Falla'], $this->oauthHeaders($tokens['access_token']))
            ->assertCreated();
    }

    public function test_grant_invalido_responde_400(): void
    {
        $this->postJson('/api/auth/oauth/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT,
            'client_secret' => 'mal',
            'username' => 'admin@lab.test',
            'password' => 'secreta123',
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');

        $this->postJson('/api/auth/oauth/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT,
            'client_secret' => self::SECRET,
            'username' => 'admin@lab.test',
            'password' => 'otra12345',
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');

        $this->postJson('/api/auth/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => self::CLIENT,
            'client_secret' => self::SECRET,
        ])->assertStatus(400)->assertJsonPath('error', 'unsupported_grant_type');
    }

    public function test_refresh_rota_y_reuso_mata_familia(): void
    {
        $tokens = $this->passwordGrant('admin@lab.test', 'secreta123');

        $rotated = $this->postJson('/api/auth/oauth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk()->json();

        $this->getJson('/api/tickets', $this->oauthHeaders($rotated['access_token']))->assertOk();

        $this->postJson('/api/auth/oauth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertStatus(400);

        $this->getJson('/api/tickets', $this->oauthHeaders($rotated['access_token']))
            ->assertUnauthorized();
    }

    public function test_revoke_e_introspect(): void
    {
        $tokens = $this->passwordGrant('admin@lab.test', 'secreta123');

        $this->postJson('/api/auth/oauth/introspect', ['token' => $tokens['access_token']])
            ->assertOk()->assertJsonPath('active', true);

        $this->postJson('/api/auth/oauth/revoke', ['token' => $tokens['access_token']])->assertNoContent();

        $this->postJson('/api/auth/oauth/introspect', ['token' => $tokens['access_token']])
            ->assertOk()->assertJsonPath('active', false);

        $this->getJson('/api/tickets', $this->oauthHeaders($tokens['access_token']))->assertUnauthorized();
    }

    public function test_viewer_no_puede_borrar_403_con_layer_rbac(): void
    {
        $admin = $this->passwordGrant('admin@lab.test', 'secreta123');
        $viewer = $this->passwordGrant('viewer@lab.test', 'secreta123');

        $id = $this->postJson('/api/tickets', ['title' => 'Falla'], $this->oauthHeaders($admin['access_token']))
            ->assertCreated()->json('id');

        $this->deleteJson("/api/tickets/{$id}", [], $this->oauthHeaders($viewer['access_token']))
            ->assertForbidden()
            ->assertJsonPath('error', 'forbidden')
            ->assertJsonPath('layer', 'rbac');
    }

    public function test_x_auth_mechanism_enruta_y_valida(): void
    {
        $admin = $this->passwordGrant('admin@lab.test', 'secreta123');

        // OAuth sin header → se trata como JWT → 401.
        $this->getJson('/api/tickets', ['Authorization' => 'Bearer '.$admin['access_token']])
            ->assertUnauthorized();

        // JWT con header oauth → 401 (sin fallback).
        $jwt = (string) $this->postJson('/api/auth/jwt/login', [
            'email' => 'admin@lab.test', 'password' => 'secreta123',
        ])->json('token');
        $this->getJson('/api/tickets', [
            'Authorization' => 'Bearer '.$jwt,
            'X-Auth-Mechanism' => 'oauth',
        ])->assertUnauthorized();

        // OAuth con header oauth → 200.
        $this->getJson('/api/tickets', $this->oauthHeaders($admin['access_token']))->assertOk();

        // JWT con header jwt explícito → 200 (regla 2).
        $this->getJson('/api/tickets', [
            'Authorization' => 'Bearer '.$jwt,
            'X-Auth-Mechanism' => 'jwt',
        ])->assertOk();

        // Valor desconocido → 400.
        $this->getJson('/api/tickets', [
            'Authorization' => 'Bearer '.$admin['access_token'],
            'X-Auth-Mechanism' => 'saml',
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_mechanism');
    }

    public function test_ticket_inexistente_da_404(): void
    {
        $admin = $this->passwordGrant('admin@lab.test', 'secreta123');

        $this->getJson('/api/tickets/999999', $this->oauthHeaders($admin['access_token']))->assertNotFound();
    }
}
