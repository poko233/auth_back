<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed de laboratorio en orden de dependencia.
     *
     * Cada seeder es idempotente y puede correrse individualmente:
     * php artisan db:seed --class=PermissionsSeeder
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            RolesSeeder::class,
            RolePermissionSeeder::class,
            UsersSeeder::class,
            AssignRolesToUsersSeeder::class,
            TicketsSeeder::class,
            OAuthClientsSeeder::class,
            DemoExperimentSeeder::class,
        ]);
    }
}
