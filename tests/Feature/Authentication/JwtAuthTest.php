<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

/**
 * Autenticación JWT end-to-end (réplica del patrón API Key/Basic).
 */
final class JwtAuthTest extends TestCase
{
    use IssuesApiKeys;
    use RefreshDatabase;

    private function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    private function makeAdmin(): int
    {
        $userId = (int) $this->postJson('/api/users', [
            'name' => 'Admin', 'email' => 'admin@lab.test', 'password' => 'secreta123',
        ])->assertCreated()->json('id');

        $this->postJson("/api/users/{$userId}/roles", ['role' => 'ADMIN'])->assertNoContent();

        return $userId;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLabRbac();
    }

    private function issueToken(string $email): string
    {
        // Opción B: el login JWT verifica identidad como Basic/OAuth.
        return (string) $this->postJson('/api/auth/jwt/login', [
            'email' => $email, 'password' => 'secreta123',
        ])->assertCreated()->assertJsonPath('mechanism', 'jwt')->json('token');
    }

    public function test_login_con_credenciales_malas_responde_401(): void
    {
        $this->makeAdmin();

        $this->postJson('/api/auth/jwt/login', ['email' => 'admin@lab.test', 'password' => 'otra12345'])
            ->assertUnauthorized()->assertJson(['error' => 'unauthenticated']);
    }

    public function test_login_emite_token_y_opera(): void
    {
        $this->makeAdmin();
        $token = $this->issueToken('admin@lab.test');

        $this->postJson('/api/tickets', ['title' => 'Falla'], $this->bearer($token))->assertCreated();
    }

    public function test_token_invalido_y_ausente_responden_401(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized()
            ->assertJson(['error' => 'unauthenticated']);

        $this->getJson('/api/tickets', $this->bearer('invalido'))->assertUnauthorized()
            ->assertJson(['error' => 'unauthenticated']);
    }

    public function test_token_revocado_responde_401(): void
    {
        $this->makeAdmin();
        $token = $this->issueToken('admin@lab.test');

        $this->getJson('/api/tickets', $this->bearer($token))->assertOk();

        $this->postJson('/api/auth/jwt/revoke', ['token' => $token])->assertNoContent();

        $this->getJson('/api/tickets', $this->bearer($token))->assertUnauthorized();
    }

    public function test_refresh_rota_y_revoca_el_anterior(): void
    {
        $this->makeAdmin();
        $token = $this->issueToken('admin@lab.test');

        $newToken = (string) $this->postJson('/api/auth/jwt/refresh', ['token' => $token])
            ->assertOk()->json('token');

        $this->assertNotSame($token, $newToken);
        $this->getJson('/api/tickets', $this->bearer($token))->assertUnauthorized();
        $this->getJson('/api/tickets', $this->bearer($newToken))->assertOk();
    }

    public function test_reuso_de_refresh_revoca_toda_la_familia(): void
    {
        $this->makeAdmin();
        $r1 = $this->issueToken('admin@lab.test');

        $r2 = (string) $this->postJson('/api/auth/jwt/refresh', ['token' => $r1])
            ->assertOk()->json('token');

        // R2 sirve (rotación normal).
        $this->getJson('/api/tickets', $this->bearer($r2))->assertOk();

        // Atacante reusa R1 → 401 y R2 queda revocado (familia muerta).
        $this->postJson('/api/auth/jwt/refresh', ['token' => $r1])->assertUnauthorized();
        $this->getJson('/api/tickets', $this->bearer($r2))->assertUnauthorized();
    }

    public function test_prune_borra_jtis_expirados(): void
    {
        \Illuminate\Support\Facades\DB::table('jwt_revocations')->insert([
            'jti' => 'viejo',
            'expires_at' => now()->subDay(),
            'revoked_at' => now()->subDay(),
        ]);

        $this->artisan('lab:prune-jwt-revocations')->assertSuccessful();

        $this->assertDatabaseMissing('jwt_revocations', ['jti' => 'viejo']);
    }

    public function test_viewer_no_puede_borrar_403_con_layer_rbac(): void
    {
        $viewerId = (int) $this->postJson('/api/users', [
            'name' => 'Viewer', 'email' => 'viewer@lab.test', 'password' => 'secreta123',
        ])->assertCreated()->json('id');
        $this->postJson("/api/users/{$viewerId}/roles", ['role' => 'VIEWER'])->assertNoContent();

        $adminId = $this->makeAdmin();
        $ticketId = $this->postJson('/api/tickets', ['title' => 'Falla'], $this->bearer($this->issueToken('admin@lab.test')))
            ->assertCreated()->json('id');

        $viewerToken = $this->issueToken('viewer@lab.test');

        $this->deleteJson("/api/tickets/{$ticketId}", [], $this->bearer($viewerToken))
            ->assertForbidden()
            ->assertJsonPath('error', 'forbidden')
            ->assertJsonPath('layer', 'rbac');
    }

    public function test_ticket_inexistente_da_404(): void
    {
        $this->makeAdmin();

        $this->getJson('/api/tickets/999999', $this->bearer($this->issueToken('admin@lab.test')))->assertNotFound();
    }
}
