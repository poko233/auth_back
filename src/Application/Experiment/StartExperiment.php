<?php

declare(strict_types=1);

namespace Lab\Application\Experiment;

use Lab\Domain\Experiment\Experiment;
use Lab\Domain\Experiment\ExperimentRepository;
use Lab\Domain\Shared\Clock;

/**
 * Inicia un experimento RUNNING sobre un mecanismo.
 */
final class StartExperiment
{
    public function __construct(
        private ExperimentRepository $experiments,
        private Clock $clock,
    ) {
    }

    public function execute(string $name, string $mechanism): Experiment
    {
        return $this->experiments->save(Experiment::start($name, $mechanism, $this->clock->now()));
    }
}
