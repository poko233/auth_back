<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Mappers;

use DateTimeImmutable;
use DateTimeInterface;
use Lab\Domain\Ticketing\Ticket;
use Lab\Domain\Ticketing\TicketId;
use Lab\Domain\Ticketing\TicketPriority;
use Lab\Domain\Ticketing\TicketStatus;
use Lab\Infrastructure\Persistence\Eloquent\Models\TicketModel;

/**
 * Traductor entre TicketModel (Eloquent) y Ticket (Domain).
 *
 * El nombre del rol asignado llega por join (assigned_role_name) desde el
 * repositorio: el mapper nunca hace queries (N+1 prohibido).
 */
final class TicketMapper
{
    public function toDomain(TicketModel $model): Ticket
    {
        return Ticket::reconstitute(
            TicketId::fromInt((int) $model->getAttribute('id')),
            (string) $model->getAttribute('title'),
            $model->getAttribute('description') !== null ? (string) $model->getAttribute('description') : null,
            TicketStatus::from((string) $model->getAttribute('status')),
            TicketPriority::from((string) $model->getAttribute('priority')),
            $model->getAttribute('created_by') !== null ? (int) $model->getAttribute('created_by') : null,
            $model->getAttribute('assigned_to') !== null ? (int) $model->getAttribute('assigned_to') : null,
            $model->getAttribute('assigned_role_name') !== null ? (string) $model->getAttribute('assigned_role_name') : null,
            $this->toImmutable($model->getAttribute('escalated_at')),
            $this->toImmutable($model->getAttribute('resolved_at')),
            $this->toImmutable($model->getAttribute('closed_at')),
            $this->toImmutable($model->getAttribute('deleted_at')),
        );
    }

    private function toImmutable(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}
