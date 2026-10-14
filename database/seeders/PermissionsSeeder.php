<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Permisos del laboratorio, todos de sistema (protegidos: no se borran por API).
 *
 * Idempotente: updateOrInsert por name.
 */
class PermissionsSeeder extends Seeder
{
    /**
     * @var array<string, string> name => description
     */
    public const PERMISSIONS = [
        'READ' => 'Leer tickets y listados',
        'WRITE' => 'Crear y modificar tickets',
        'DELETE' => 'Eliminar tickets (soft delete)',
        'ADMIN' => 'Gestionar roles, permisos y asignaciones',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name => $description) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['description' => $description, 'is_system' => true, 'updated_at' => now()],
            );
        }
    }
}
