<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Deja un experimento demo listo (no lo corre: eso lo hace lab:smoke-experiment).
 */
class DemoExperimentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('experiments')->updateOrInsert(
            ['name' => 'Demo escalado VIEWER→ADMIN'],
            [
                'mechanism' => 'api_key',
                'status' => 'PENDING',
                'updated_at' => now(),
            ],
        );
    }
}
