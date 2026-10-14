<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

/**
 * Reglas del auth multimecanismo: orden fijo, sin fallback, mechanism real.
 */
final class FlexibleAuthTest extends TestCase
{
    use IssuesApiKeys;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLabRbac();
    }

    public function test_con_dos_mecanismos_gana_x_api_key_por_orden(): void
    {
        $viewer = $this->makeUserWithKey('viewer@lab.test', 'VIEWER');

        $adminId = (int) $this->postJson('/api/users', [
            'name' => 'Admin', 'email' => 'admin@lab.test', 'password' => 'secreta123',
        ])->assertCreated()->json('id');
        $this->postJson("/api/users/{$adminId}/roles", ['role' => 'ADMIN'])->assertNoContent();

        $adminBasic = ['Authorization' => 'Basic '.base64_encode('admin@lab.test:secreta123')];
        $ticketId = $this->postJson('/api/tickets', ['title' => 'Falla'], $adminBasic)->assertCreated()->json('id');

        // Viewer (key válida) + admin (basic válido): si ganara Basic → 204.
        // Gana X-API-Key por orden → principal viewer → 403 con layer rbac.
        $this->deleteJson(
            "/api/tickets/{$ticketId}",
            [],
            array_merge($this->keyHeaders($viewer['key']), $adminBasic),
        )->assertForbidden()->assertJsonPath('layer', 'rbac');
    }

    public function test_sin_fallback_key_invalida_mas_basic_valido_da_401(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Admin', 'email' => 'admin@lab.test', 'password' => 'secreta123',
        ])->assertCreated();

        $this->getJson('/api/tickets', array_merge(
            ['X-API-Key' => str_repeat('0', 64)],
            ['Authorization' => 'Basic '.base64_encode('admin@lab.test:secreta123')],
        ))->assertUnauthorized()->assertJsonPath('error', 'unauthenticated');
    }
}
