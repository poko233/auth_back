<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Lab\Domain\Ticketing\TicketHistoryEntry;
use Lab\Domain\Ticketing\TicketHistoryRepository;
use Lab\Domain\Ticketing\TicketId;
use Lab\Infrastructure\Persistence\Eloquent\Models\TicketHistoryModel;

/**
 * Implementación Eloquent del puerto de la bitácora.
 */
final class EloquentTicketHistoryRepository implements TicketHistoryRepository
{
    public function record(TicketHistoryEntry $entry): void
    {
        $model = new TicketHistoryModel([
            'ticket_id' => $entry->ticketId->value(),
            'actor_id' => $entry->actorId,
            'action' => $entry->action,
            'from_value' => $entry->fromValue,
            'to_value' => $entry->toValue,
            'created_at' => $entry->createdAt,
        ]);
        $model->save();
    }

    public function listForTicket(TicketId $ticketId): array
    {
        $models = TicketHistoryModel::query()
            ->select(['id', 'ticket_id', 'actor_id', 'action', 'from_value', 'to_value', 'created_at'])
            ->where('ticket_id', $ticketId->value())
            ->orderBy('created_at')
            ->get();

        $entries = [];

        foreach ($models as $model) {
            $entries[] = new TicketHistoryEntry(
                (int) $model->getAttribute('id'),
                TicketId::fromInt((int) $model->getAttribute('ticket_id')),
                $model->getAttribute('actor_id') !== null ? (int) $model->getAttribute('actor_id') : null,
                (string) $model->getAttribute('action'),
                $model->getAttribute('from_value') !== null ? (string) $model->getAttribute('from_value') : null,
                $model->getAttribute('to_value') !== null ? (string) $model->getAttribute('to_value') : null,
                $this->toImmutable($model->getAttribute('created_at')),
            );
        }

        return $entries;
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
