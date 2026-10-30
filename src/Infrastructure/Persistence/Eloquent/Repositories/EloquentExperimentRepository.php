<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Lab\Domain\Experiment\Experiment;
use Lab\Domain\Experiment\ExperimentEvent;
use Lab\Domain\Experiment\ExperimentRepository;
use Lab\Domain\Experiment\ExperimentStatus;
use Lab\Domain\Identity\UserId;
use Lab\Infrastructure\Persistence\Eloquent\Models\ExperimentEventModel;
use Lab\Infrastructure\Persistence\Eloquent\Models\ExperimentModel;

/**
 * Implementación Eloquent del puerto de experimentos.
 */
final class EloquentExperimentRepository implements ExperimentRepository
{
    private const COLUMNS = ['id', 'name', 'mechanism', 'status', 'test_user_id', 'resources', 'created_at', 'started_at', 'finished_at'];

    public function save(Experiment $experiment): Experiment
    {
        if ($experiment->id() !== null) {
            $model = ExperimentModel::query()->where('id', $experiment->id())->first();
        } else {
            $model = null;
        }

        if ($model === null) {
            $model = new ExperimentModel();
            $model->setAttribute('name', $experiment->name());
            $model->setAttribute('mechanism', $experiment->mechanism());
            $model->setAttribute('test_user_id', $experiment->testUserId()?->value());
            $model->setAttribute('resources', $experiment->resources());

            if ($experiment->createdAt() !== null) {
                $model->setAttribute('created_at', $experiment->createdAt());
            }
        }

        $model->setAttribute('started_at', $this->formatOrNull($experiment->startedAt()));
        $model->setAttribute('status', $experiment->status()->value);
        $model->setAttribute('finished_at', $this->formatOrNull($experiment->finishedAt()));
        $model->save();
        $model->refresh();

        return $this->toDomain($model);
    }

    public function transition(Experiment $experiment, ExperimentStatus $expectedStatus): ?Experiment
    {
        $updated = ExperimentModel::query()
            ->where('id', $experiment->id())
            ->where('status', $expectedStatus->value)
            ->update([
                'status' => $experiment->status()->value,
                'started_at' => $this->formatOrNull($experiment->startedAt()),
                'finished_at' => $this->formatOrNull($experiment->finishedAt()),
            ]);

        return $updated === 1 ? $this->findById((int) $experiment->id()) : null;
    }

    public function findById(int $id): ?Experiment
    {
        $model = ExperimentModel::query()->select(self::COLUMNS)->where('id', $id)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function all(): array
    {
        $models = ExperimentModel::query()->select(self::COLUMNS)->orderBy('id')->get();
        $experiments = [];

        foreach ($models as $model) {
            $experiments[] = $this->toDomain($model);
        }

        return $experiments;
    }

    public function recordEvent(ExperimentEvent $event): void
    {
        $model = new ExperimentEventModel([
            'experiment_id' => $event->experimentId,
            'event_type' => $event->eventType,
            'payload' => $event->payload,
            'created_at' => $event->createdAt,
        ]);
        $model->save();
    }

    public function eventsFor(int $experimentId): array
    {
        $models = ExperimentEventModel::query()
            ->select(['id', 'experiment_id', 'event_type', 'payload', 'created_at'])
            ->where('experiment_id', $experimentId)
            ->orderBy('created_at')
            ->get();

        $events = [];

        foreach ($models as $model) {
            $events[] = new ExperimentEvent(
                (int) $model->getAttribute('id'),
                (int) $model->getAttribute('experiment_id'),
                (string) $model->getAttribute('event_type'),
                (array) $model->getAttribute('payload'),
                $this->toImmutable($model->getAttribute('created_at')),
            );
        }

        return $events;
    }

    private function toDomain(ExperimentModel $model): Experiment
    {
        return Experiment::reconstitute(
            (int) $model->getAttribute('id'),
            (string) $model->getAttribute('name'),
            (string) $model->getAttribute('mechanism'),
            ExperimentStatus::from((string) $model->getAttribute('status')),
            $this->toImmutableOrNull($model->getAttribute('started_at')),
            $this->toImmutableOrNull($model->getAttribute('finished_at')),
            $model->getAttribute('test_user_id') === null ? null : UserId::fromString((string) $model->getAttribute('test_user_id')),
            $model->getAttribute('resources'),
            $this->toImmutableOrNull($model->getAttribute('created_at')),
        );
    }

    private function formatOrNull(?DateTimeImmutable $value): ?string
    {
        return $value?->format('Y-m-d H:i:s');
    }

    private function toImmutableOrNull(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        return $this->toImmutable($value);
    }

    private function toImmutable(mixed $value): DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}
