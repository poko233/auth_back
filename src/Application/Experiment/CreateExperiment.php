<?php

declare(strict_types=1);

namespace Lab\Application\Experiment;

use Lab\Domain\Experiment\Experiment;
use Lab\Domain\Experiment\ExperimentRepository;
use Lab\Domain\Identity\Exception\UserNotFound;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Identity\UserRepository;
use Lab\Domain\Shared\Clock;

final class CreateExperiment
{
    public function __construct(private ExperimentRepository $experiments, private UserRepository $users, private Clock $clock)
    {
    }

    /** @param list<string> $resources */
    public function execute(string $name, string $mechanism, UserId $testUserId, array $resources): Experiment
    {
        if ($this->users->findById($testUserId) === null) {
            throw new UserNotFound('El usuario de prueba no existe.');
        }

        return $this->experiments->save(Experiment::create($name, $mechanism, $testUserId, $resources, $this->clock->now()));
    }
}
