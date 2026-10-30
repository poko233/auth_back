<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\ExercisesMetricsCollection;
use Tests\TestCase;

final class MetricsSupabaseSmokeTest extends TestCase
{
    use DatabaseTransactions;
    use ExercisesMetricsCollection;

    protected function setUp(): void
    {
        if (getenv('RUN_SUPABASE_SMOKE') !== '1') {
            $this->markTestSkipped('Prueba Supabase opt-in con rollback; no migra ni siembra.');
        }
        parent::setUp();
        $this->assertSame('pgsql', config('database.default'));
    }

    public function test_metrics_on_supabase_with_rollback(): void
    {
        $this->exerciseMetricsCollection();
    }
}
