<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Cliente OAuth demo del laboratorio (grant password).
 *
 * Idempotente: updateOrInsert por client_id. Secreto demo: 'lab-secret'.
 */
class OAuthClientsSeeder extends Seeder
{
    public const CLIENT_ID = 'lab-client';

    public const CLIENT_SECRET = 'lab-secret';

    public function run(): void
    {
        DB::table('oauth_clients')->updateOrInsert(
            ['client_id' => self::CLIENT_ID],
            [
                'client_secret' => Hash::make(self::CLIENT_SECRET),
                'name' => 'Laboratorio Auth Lab',
                'updated_at' => now(),
            ],
        );
    }
}
