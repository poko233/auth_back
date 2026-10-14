<?php

declare(strict_types=1);

namespace Lab\Domain\Experiment;

use DateTimeImmutable;

/**
 * Experimento: una corrida del escenario sobre un mecanismo.
 */
final class Experiment
{
    private function __construct(
        private ?int $id,
        private string $name,
        private string $mechanism,
        private ExperimentStatus $status,
        private ?DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $finishedAt,
    ) {
    }

    public static function start(string $name, string $mechanism, DateTimeImmutable $now): self
    {
        return new self(null, $name, $mechanism, ExperimentStatus::RUNNING, $now, null);
    }

    public static function reconstitute(int $id, string $name, string $mechanism, ExperimentStatus $status, ?DateTimeImmutable $startedAt, ?DateTimeImmutable $finishedAt): self
    {
        return new self($id, $name, $mechanism, $status, $startedAt, $finishedAt);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function mechanism(): string
    {
        return $this->mechanism;
    }

    public function status(): ExperimentStatus
    {
        return $this->status;
    }

    public function startedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function finishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function finish(ExperimentStatus $status, DateTimeImmutable $now): void
    {
        $this->status = $status;
        $this->finishedAt = $now;
    }
}
