<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Asigna cada usuario demo a su rol base.
 *
 * Idempotente: updateOrInsert por la PK compuesta (user_id, role_id).
 */
class AssignRolesToUsersSeeder extends Seeder
{
    /**
     * @var array<string, string> email => role
     */
    public const MAP = [
        'viewer@lab.test' => 'VIEWER',
        'editor@lab.test' => 'EDITOR',
        'admin@lab.test' => 'ADMIN',
    ];

    public function run(): void
    {
        foreach (self::MAP as $email => $roleName) {
            $userId = DB::table('users')->where('email', $email)->value('id');
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');

            if ($userId === null || $roleId === null) {
                $this->command?->warn("Falta usuario {$email} o rol {$roleName}, ejecuta UsersSeeder y RolesSeeder primero.");

                continue;
            }

            DB::table('role_user')->updateOrInsert(
                ['user_id' => $userId, 'role_id' => $roleId],
                ['assigned_at' => now()],
            );
        }
    }
}
