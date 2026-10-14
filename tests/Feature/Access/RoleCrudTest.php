<?php

declare(strict_types=1);

namespace Tests\Feature\Access;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CRUD de roles + asignación a usuarios end-to-end.
 */
final class RoleCrudTest extends TestCase
{
    use RefreshDatabase;

    private function seedBase(): void
    {
        foreach (['READ', 'WRITE', 'DELETE', 'ADMIN'] as $name) {
            DB::table('permissions')->insert(['name' => $name, 'is_system' => true]);
        }

        foreach (['VIEWER', 'EDITOR', 'ADMIN'] as $name) {
            DB::table('roles')->insert(['name' => $name, 'is_system' => true]);
        }

        $read = DB::table('permissions')->where('name', 'READ')->value('id');
        $viewer = DB::table('roles')->where('name', 'VIEWER')->value('id');
        DB::table('role_permission')->insert(['role_id' => $viewer, 'permission_id' => $read]);
    }

    public function test_crud_de_rol_libre_y_proteccion_de_sistema(): void
    {
        $this->seedBase();

        $this->getJson('/api/roles')->assertOk()->assertJsonFragment(['name' => 'VIEWER']);

        $this->postJson('/api/roles', ['name' => 'SUPPORT', 'description' => 'Soporte', 'permissions' => ['READ']])
            ->assertCreated()->assertJsonFragment(['name' => 'SUPPORT']);

        $this->putJson('/api/roles/SUPPORT', ['description' => 'Soporte N1'])
            ->assertOk()->assertJsonFragment(['description' => 'Soporte N1']);

        $this->putJson('/api/roles/SUPPORT/permissions', ['permissions' => ['READ', 'WRITE']])
            ->assertNoContent();

        // Los roles de sistema no se tocan.
        $this->deleteJson('/api/roles/VIEWER')->assertConflict();
        $this->putJson('/api/roles/VIEWER', ['description' => 'X'])->assertConflict();

        $this->deleteJson('/api/roles/SUPPORT')->assertNoContent();
    }

    public function test_asigna_y_revoca_rol_a_usuario(): void
    {
        $this->seedBase();

        $userId = DB::table('users')->insertGetId([
            'name' => 'Ana',
            'email' => 'ana@lab.test',
            'password' => 'x',
            'is_active' => true,
        ]);

        $this->postJson("/api/users/{$userId}/roles", ['role' => 'VIEWER'])->assertNoContent();
        $this->assertDatabaseHas('role_user', ['user_id' => $userId]);

        $this->deleteJson("/api/users/{$userId}/roles/VIEWER")->assertNoContent();
        $this->assertDatabaseMissing('role_user', ['user_id' => $userId]);
    }
}
