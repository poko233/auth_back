<?php

declare(strict_types=1);

namespace Lab\Application\Experiment;

use Lab\Domain\Experiment\Exception\ExperimentNotFound;
use Lab\Domain\Experiment\Experiment;
use Lab\Domain\Experiment\ExperimentRepository;
use Lab\Domain\Experiment\ExperimentStatus;
use Lab\Domain\Shared\Clock;

/**
 * Cierra un experimento como COMPLETED o FAILED.
 */
final class FinishExperiment
{
    public function __construct(
        private ExperimentRepository $experiments,
        private Clock $clock,
    ) {
    }

    public function execute(int $id, ExperimentStatus $status = ExperimentStatus::COMPLETED): Experiment
    {
        $experiment = $this->experiments->findById($id);

        if ($experiment === null) {
            throw new ExperimentNotFound("Experimento no encontrado: {$id}.");
        }

        $experiment->finish($status, $this->clock->now());

        return $this->experiments->save($experiment);
    }
}
