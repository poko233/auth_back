<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios demo del laboratorio (uno por rol base).
 *
 * Idempotente: updateOrInsert por email. Password de demo: 'password'.
 */
class UsersSeeder extends Seeder
{
    /**
     * @var array<string, string> email => name
     */
    public const USERS = [
        'viewer@lab.test' => 'Viewer Demo',
        'editor@lab.test' => 'Editor Demo',
        'admin@lab.test' => 'Admin Demo',
    ];

    public function run(): void
    {
        foreach (self::USERS as $email => $name) {
            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'updated_at' => now(),
                ],
            );
        }
    }
}
