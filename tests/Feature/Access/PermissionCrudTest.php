<?php

declare(strict_types=1);

namespace Tests\Feature\Access;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CRUD de permisos dinámicos end-to-end.
 */
final class PermissionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_de_permiso_libre_y_proteccion_de_sistema(): void
    {
        DB::table('permissions')->insert(['name' => 'READ', 'is_system' => true]);

        $this->getJson('/api/permissions')->assertOk()->assertJsonFragment(['name' => 'READ']);

        $this->postJson('/api/permissions', ['name' => 'EXPORT', 'description' => 'Exportar'])
            ->assertCreated()->assertJsonFragment(['name' => 'EXPORT']);

        $this->putJson('/api/permissions/EXPORT', ['description' => 'Exportar CSV'])
            ->assertOk()->assertJsonFragment(['description' => 'Exportar CSV']);

        // Los permisos de sistema no se tocan.
        $this->putJson('/api/permissions/READ', ['description' => 'X'])->assertConflict();
        $this->deleteJson('/api/permissions/READ')->assertConflict();

        $this->deleteJson('/api/permissions/EXPORT')->assertNoContent();
    }

    public function test_no_borra_permiso_asignado_a_rol(): void
    {
        $permissionId = DB::table('permissions')->insertGetId(['name' => 'EXPORT', 'is_system' => false]);
        $roleId = DB::table('roles')->insertGetId(['name' => 'SUPPORT', 'is_system' => false]);
        DB::table('role_permission')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);

        $this->deleteJson('/api/permissions/EXPORT')->assertConflict();
    }
}
