<?php

declare(strict_types=1);

namespace Lab\Domain\Experiment;

/**
 * Puerto de experimentos y eventos.
 */
interface ExperimentRepository
{
    public function save(Experiment $experiment): Experiment;

    /** Guarda las fechas y el estado solo si el estado persistido todavía coincide. */
    public function transition(Experiment $experiment, ExperimentStatus $expectedStatus): ?Experiment;

    public function findById(int $id): ?Experiment;

    /**
     * @return list<Experiment>
     */
    public function all(): array;

    public function recordEvent(ExperimentEvent $event): void;

    /**
     * @return list<ExperimentEvent>
     */
    public function eventsFor(int $experimentId): array;
}
