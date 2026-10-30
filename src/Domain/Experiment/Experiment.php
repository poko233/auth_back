<?php

declare(strict_types=1);

namespace Lab\Domain\Experiment;

use DateTimeImmutable;
use InvalidArgumentException;
use Lab\Domain\Experiment\Exception\ExperimentStateConflict;
use Lab\Domain\Identity\UserId;

/**
 * Experimento: una corrida del escenario sobre un mecanismo.
 */
final class Experiment
{
    public const MECHANISMS = ['api_key', 'basic', 'jwt', 'oauth'];

    public const RESOURCES = ['tickets.list'];

    /** @param list<string>|null $resources */
    private function __construct(
        private ?int $id,
        private string $name,
        private string $mechanism,
        private ExperimentStatus $status,
        private ?DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $finishedAt,
        private ?UserId $testUserId = null,
        private ?array $resources = null,
        private ?DateTimeImmutable $createdAt = null,
    ) {
    }

    /** @param list<string> $resources */
    public static function create(string $name, string $mechanism, UserId $testUserId, array $resources, DateTimeImmutable $now): self
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 255) {
            throw new InvalidArgumentException('El nombre del experimento es obligatorio y no puede superar los 255 caracteres.');
        }

        if (! in_array($mechanism, self::MECHANISMS, true)) {
            throw new InvalidArgumentException('Debe seleccionar un mecanismo válido.');
        }

        if ($resources === [] || count(array_unique($resources)) !== count($resources) || array_diff($resources, self::RESOURCES) !== []) {
            throw new InvalidArgumentException('Debe seleccionar un recurso permitido.');
        }

        return new self(null, $name, $mechanism, ExperimentStatus::PENDING, null, null, $testUserId, array_values($resources), $now);
    }

    public static function start(string $name, string $mechanism, DateTimeImmutable $now): self
    {
        return new self(null, $name, $mechanism, ExperimentStatus::RUNNING, $now, null);
    }

    /** @param list<string>|null $resources */
    public static function reconstitute(int $id, string $name, string $mechanism, ExperimentStatus $status, ?DateTimeImmutable $startedAt, ?DateTimeImmutable $finishedAt, ?UserId $testUserId = null, ?array $resources = null, ?DateTimeImmutable $createdAt = null): self
    {
        return new self($id, $name, $mechanism, $status, $startedAt, $finishedAt, $testUserId, $resources, $createdAt);
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

    public function testUserId(): ?UserId
    {
        return $this->testUserId;
    }

    /** @return list<string>|null */
    public function resources(): ?array
    {
        return $this->resources;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function begin(DateTimeImmutable $now): void
    {
        if ($this->status === ExperimentStatus::COMPLETED) {
            throw new ExperimentStateConflict('experiment_already_executed', 'Este experimento ya fue ejecutado');
        }

        if ($this->status === ExperimentStatus::RUNNING) {
            throw new ExperimentStateConflict('experiment_running', 'Este experimento ya está en ejecución');
        }

        if ($this->status !== ExperimentStatus::PENDING) {
            throw new ExperimentStateConflict('experiment_failed', 'Un experimento fallido no puede volver a iniciarse');
        }

        if ($this->testUserId === null || $this->resources === null || $this->resources === []) {
            throw new InvalidArgumentException('Este experimento histórico no tiene usuario y recursos configurados.');
        }

        $this->status = ExperimentStatus::RUNNING;
        $this->startedAt = $now;
    }

    public function finish(ExperimentStatus $status, DateTimeImmutable $now): void
    {
        if ($this->status !== ExperimentStatus::RUNNING) {
            throw new ExperimentStateConflict('experiment_not_running', 'Solo se puede finalizar un experimento en ejecución');
        }

        if (! in_array($status, [ExperimentStatus::COMPLETED, ExperimentStatus::FAILED], true)) {
            throw new InvalidArgumentException('El estado final debe ser COMPLETED o FAILED.');
        }

        $this->status = $status;
        $this->finishedAt = $now;
    }
}
