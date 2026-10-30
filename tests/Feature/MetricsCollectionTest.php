<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ExercisesMetricsCollection;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

final class MetricsCollectionTest extends TestCase
{
    use ExercisesMetricsCollection;
    use IssuesApiKeys;
    use RefreshDatabase;

    public function test_records_each_request_and_compares_operations_for_all_mechanisms(): void
    {
        $this->seedLabRbac();
        $this->exerciseMetricsCollection();
    }
}
