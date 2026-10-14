<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

/**
 * Autenticación Basic end-to-end (réplica del patrón API Key).
 */
final class BasicAuthTest extends TestCase
{
    use IssuesApiKeys;
    use RefreshDatabase;

    private function basicHeaders(string $email, string $password): array
    {
        return ['Authorization' => 'Basic '.base64_encode("{$email}:{$password}")];
    }

    private function makeUser(string $email, string $role): void
    {
        $userId = (int) $this->postJson('/api/users', [
            'name' => $email, 'email' => $email, 'password' => 'secreta123',
        ])->assertCreated()->json('id');

        $this->postJson("/api/users/{$userId}/roles", ['role' => $role])->assertNoContent();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLabRbac();
        $this->makeUser('admin@lab.test', 'ADMIN');
        $this->makeUser('viewer@lab.test', 'VIEWER');
    }

    public function test_login_con_credenciales_validas(): void
    {
        $this->postJson('/api/auth/basic/login', ['email' => 'admin@lab.test', 'password' => 'secreta123'])
            ->assertOk()
            ->assertJsonPath('mechanism', 'basic')
            ->assertJsonPath('user.email', 'admin@lab.test');
    }

    public function test_login_fallidos_responden_401_con_mismo_body(): void
    {
        $expected = ['error' => 'unauthenticated'];

        $this->postJson('/api/auth/basic/login', ['email' => 'admin@lab.test', 'password' => 'otra12345'])
            ->assertUnauthorized()->assertJsonFragment($expected);

        $this->postJson('/api/auth/basic/login', ['email' => 'nadie@lab.test', 'password' => 'secreta123'])
            ->assertUnauthorized()->assertJsonFragment($expected);
    }

    public function test_usuario_inactivo_responde_401(): void
    {
        $userId = (int) $this->postJson('/api/users', [
            'name' => 'Off', 'email' => 'off@lab.test', 'password' => 'secreta123',
        ])->assertCreated()->json('id');

        $this->postJson("/api/users/{$userId}/deactivate")->assertOk();

        $this->postJson('/api/auth/basic/login', ['email' => 'off@lab.test', 'password' => 'secreta123'])
            ->assertUnauthorized()->assertJsonFragment(['error' => 'unauthenticated']);
    }

    public function test_headers_malformados_responden_401(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized()
            ->assertJsonFragment(['error' => 'unauthenticated']);

        $this->getJson('/api/tickets', ['Authorization' => 'Basic'])
            ->assertUnauthorized()->assertJsonFragment(['error' => 'unauthenticated']);

        $this->getJson('/api/tickets', ['Authorization' => 'Basic !!!'])
            ->assertUnauthorized()->assertJsonFragment(['error' => 'unauthenticated']);

        $this->getJson('/api/tickets', ['Authorization' => 'Basic '.base64_encode('sin-separador')])
            ->assertUnauthorized()->assertJsonFragment(['error' => 'unauthenticated']);
    }

    public function test_viewer_no_puede_borrar_403_con_layer_rbac(): void
    {
        $admin = $this->basicHeaders('admin@lab.test', 'secreta123');
        $id = $this->postJson('/api/tickets', ['title' => 'Falla'], $admin)->assertCreated()->json('id');

        $this->deleteJson("/api/tickets/{$id}", [], $this->basicHeaders('viewer@lab.test', 'secreta123'))
            ->assertForbidden()
            ->assertJsonPath('error', 'forbidden')
            ->assertJsonPath('layer', 'rbac');
    }

    public function test_admin_opera_y_ticket_inexistente_da_404(): void
    {
        $admin = $this->basicHeaders('admin@lab.test', 'secreta123');

        $id = $this->postJson('/api/tickets', ['title' => 'Falla'], $admin)->assertCreated()->json('id');
        $this->deleteJson("/api/tickets/{$id}", [], $admin)->assertNoContent();
        $this->getJson('/api/tickets/999999', $admin)->assertNotFound();
    }

    public function test_principal_viene_del_header_basic(): void
    {
        // Viewer autenticado como viewer no puede crear (solo READ).
        $this->postJson('/api/tickets', ['title' => 'Falla'], $this->basicHeaders('viewer@lab.test', 'secreta123'))
            ->assertForbidden()->assertJsonPath('layer', 'rbac');
    }
}
