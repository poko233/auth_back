<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Roles base del laboratorio, todos de sistema (protegidos).
 *
 * Idempotente: updateOrInsert por name.
 */
class RolesSeeder extends Seeder
{
    /**
     * @var array<string, string> name => description
     */
    public const ROLES = [
        'VIEWER' => 'Solo lectura de tickets',
        'EDITOR' => 'Lectura y escritura de tickets',
        'ADMIN' => 'Acceso total más gestión del laboratorio',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $name => $description) {
            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                ['description' => $description, 'is_system' => true, 'updated_at' => now()],
            );
        }
    }
}
