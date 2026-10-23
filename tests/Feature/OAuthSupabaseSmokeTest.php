<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\ExercisesOAuthFlow;
use Tests\TestCase;

/** Prueba opt-in sobre Postgres real: no migra ni siembra y revierte todas sus escrituras. */
final class OAuthSupabaseSmokeTest extends TestCase
{
    use DatabaseTransactions;
    use ExercisesOAuthFlow;

    protected function setUp(): void
    {
        if (getenv('RUN_SUPABASE_SMOKE') !== '1') {
            $this->markTestSkipped('Activa RUN_SUPABASE_SMOKE=1 para comprobar el Postgres del .env.');
        }
        parent::setUp();
        $this->assertSame('pgsql', config('database.default'));
    }

    public function test_flujo_oauth_real_con_rollback(): void
    {
        $this->exerciseOAuthFlow();
    }
}
