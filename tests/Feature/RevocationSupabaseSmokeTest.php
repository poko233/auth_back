<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\ExercisesRevocationComparison;
use Tests\TestCase;

final class RevocationSupabaseSmokeTest extends TestCase
{
    use DatabaseTransactions;
    use ExercisesRevocationComparison;

    protected function setUp(): void
    {
        if (getenv('RUN_SUPABASE_SMOKE') !== '1') {
            $this->markTestSkipped('Prueba opt-in con rollback; no migra ni siembra.');
        }
        parent::setUp();
        $this->assertSame('pgsql', config('database.default'));
    }

    public function test_revocation_evidence_on_postgres_with_rollback(): void
    {
        $this->exerciseRevocationComparison();
    }
}
