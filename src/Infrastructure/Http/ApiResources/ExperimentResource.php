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
        ];
    }
}
