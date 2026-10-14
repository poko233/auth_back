<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pivote rol → permiso del laboratorio.
 *
 * Idempotente: updateOrInsert por la PK compuesta (role_id, permission_id).
 * Nunca attach() puro: la PK compuesta explotaría con duplicados.
 *
 * Mapa (AGENTS.md §6):
 *   VIEWER → READ
 *   EDITOR → READ, WRITE
 *   ADMIN  → READ, WRITE, DELETE, ADMIN
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    public const MAP = [
        'VIEWER' => ['READ'],
        'EDITOR' => ['READ', 'WRITE'],
        'ADMIN' => ['READ', 'WRITE', 'DELETE', 'ADMIN'],
    ];

    public function run(): void
    {
        foreach (self::MAP as $role => $permissions) {
            $roleId = DB::table('roles')->where('name', $role)->value('id');

            if ($roleId === null) {
                $this->command?->warn("Rol {$role} no existe, ejecuta RolesSeeder primero.");

                continue;
            }

            foreach ($permissions as $permission) {
                $permissionId = DB::table('permissions')->where('name', $permission)->value('id');

                if ($permissionId === null) {
                    $this->command?->warn("Permiso {$permission} no existe, ejecuta PermissionsSeeder primero.");

                    continue;
                }

                DB::table('role_permission')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                );
            }
        }
    }
}
