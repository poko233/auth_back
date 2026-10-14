<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

/**
 * Autenticación por API Key end-to-end.
 *
 * El principal viene EXCLUSIVAMENTE del key: no hay headers adicionales.
 */
final class ApiKeyAuthTest extends TestCase
{
    use IssuesApiKeys;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLabRbac();
    }

    public function test_login_con_key_valida_devuelve_usuario_y_mecanismo(): void
    {
        $admin = $this->makeUserWithKey('admin@lab.test', 'ADMIN');

        $this->postJson('/api/auth/api-key/login', ['key' => $admin['key']])
            ->assertOk()
            ->assertJsonPath('mechanism', 'api_key')
            ->assertJsonPath('user.email', 'admin@lab.test');
    }

    public function test_login_con_key_invalida_responde_401(): void
    {
        $this->postJson('/api/auth/api-key/login', ['key' => str_repeat('a', 64)])
            ->assertUnauthorized();
    }

    public function test_key_expirada_responde_401(): void
    {
        $userId = (int) $this->postJson('/api/users', [
            'name' => 'Ana', 'email' => 'ana@lab.test', 'password' => 'secreta123',
        ])->assertCreated()->json('id');

        $plain = (string) $this->postJson('/api/auth/api-key/issue', [
            'user_id' => $userId,
            'expires_at' => now()->subMinute()->toIso8601String(),
        ])->assertCreated()->json('plain');

        $this->postJson('/api/auth/api-key/login', ['key' => $plain])->assertUnauthorized();
        $this->getJson('/api/tickets', $this->keyHeaders($plain))->assertUnauthorized();
    }

    public function test_key_revocada_responde_401(): void
    {
        $admin = $this->makeUserWithKey('admin@lab.test', 'ADMIN');
        $keyId = (int) DB::table('api_keys')->value('id');

        $this->getJson('/api/tickets', $this->keyHeaders($admin['key']))->assertOk();

        $this->postJson("/api/auth/api-key/revoke/{$keyId}")->assertNoContent();

        $this->getJson('/api/tickets', $this->keyHeaders($admin['key']))->assertUnauthorized();
    }

    public function test_viewer_no_puede_borrar_403_con_layer_rbac(): void
    {
        $viewer = $this->makeUserWithKey('viewer@lab.test', 'VIEWER');
        $admin = $this->makeUserWithKey('admin@lab.test', 'ADMIN');

        $id = $this->postJson('/api/tickets', ['title' => 'Falla'], $this->keyHeaders($admin['key']))
            ->assertCreated()->json('id');

        $this->deleteJson("/api/tickets/{$id}", [], $this->keyHeaders($viewer['key']))
            ->assertForbidden()
            ->assertJsonPath('error', 'forbidden')
            ->assertJsonPath('layer', 'rbac');
    }

    public function test_admin_no_puede_borrar_closed_403_con_layer_ticket_rule(): void
    {
        $admin = $this->makeUserWithKey('admin@lab.test', 'ADMIN');
        $headers = $this->keyHeaders($admin['key']);

        $id = $this->postJson('/api/tickets', ['title' => 'Falla'], $headers)->assertCreated()->json('id');
        $this->postJson("/api/tickets/{$id}/status", ['status' => 'CLOSED'], $headers)->assertOk();

        $this->deleteJson("/api/tickets/{$id}", [], $headers)
            ->assertForbidden()
            ->assertJsonPath('error', 'forbidden')
            ->assertJsonPath('layer', 'ticket_rule');
    }

    public function test_viewer_no_puede_crear_403_con_layer_rbac(): void
    {
        $viewer = $this->makeUserWithKey('viewer@lab.test', 'VIEWER');

        $this->postJson('/api/tickets', ['title' => 'Falla'], $this->keyHeaders($viewer['key']))
            ->assertForbidden()
            ->assertJsonPath('layer', 'rbac');
    }

    public function test_admin_opera_ticket_abierto_sin_bloqueos(): void
    {
        $admin = $this->makeUserWithKey('admin@lab.test', 'ADMIN');
        $headers = $this->keyHeaders($admin['key']);

        $id = $this->postJson('/api/tickets', ['title' => 'Falla'], $headers)->assertCreated()->json('id');
        $this->deleteJson("/api/tickets/{$id}", [], $headers)->assertNoContent();
    }
}
