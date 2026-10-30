<?php

declare(strict_types=1);

namespace Lab\Application\Experiment;

use Lab\Domain\Experiment\Exception\ExperimentNotFound;
use Lab\Domain\Experiment\Exception\ExperimentStateConflict;
use Lab\Domain\Experiment\Experiment;
use Lab\Domain\Experiment\ExperimentRepository;
use Lab\Domain\Experiment\ExperimentStatus;
use Lab\Domain\Shared\Clock;

final class BeginExperiment
{
    public function __construct(private ExperimentRepository $experiments, private Clock $clock)
    {
    }

    public function execute(int $id): Experiment
    {
        $experiment = $this->experiments->findById($id);

        if ($experiment === null) {
            throw new ExperimentNotFound("Experimento no encontrado: {$id}.");
        }

        $experiment->begin($this->clock->now());
        $saved = $this->experiments->transition($experiment, ExperimentStatus::PENDING);

        if ($saved === null) {
            throw new ExperimentStateConflict('experiment_state_changed', 'El experimento cambió de estado; actualice el listado');
        }

        return $saved;
    }
}
