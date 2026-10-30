<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\ApiResources;

use Lab\Domain\Experiment\Experiment;

/**
 * Representación JSON de un experimento.
 */
final class ExperimentResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Experiment $experiment): array
    {
        return [
            'id' => $experiment->id(),
            'name' => $experiment->name(),
            'mechanism' => $experiment->mechanism(),
            'status' => $experiment->status()->value,
            'test_user_id' => $experiment->testUserId()?->value(),
            'resources' => $experiment->resources(),
            'created_at' => $experiment->createdAt()?->format(DATE_ATOM),
            'started_at' => $experiment->startedAt()?->format(DATE_ATOM),
            'finished_at' => $experiment->finishedAt()?->format(DATE_ATOM),
        ];
    }
}
