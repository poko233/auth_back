<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Lab\Domain\Ticketing\TicketEscalation;
use Lab\Domain\Ticketing\TicketEscalationRepository;
use Lab\Domain\Ticketing\TicketId;
use Lab\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Lab\Infrastructure\Persistence\Eloquent\Models\TicketEscalationModel;

/**
 * Implementación Eloquent del puerto de escalamientos.
 */
final class EloquentTicketEscalationRepository implements TicketEscalationRepository
{
    public function record(TicketEscalation $escalation): void
    {
        $model = new TicketEscalationModel([
            'ticket_id' => $escalation->ticketId->value(),
            'from_role_id' => $this->roleIdByName($escalation->fromRole),
            'to_role_id' => $this->roleIdByName($escalation->toRole),
            'escalated_by' => $escalation->escalatedBy,
            'escalated_at' => $escalation->escalatedAt,
            'note' => $escalation->note,
        ]);
        $model->save();
    }

    public function listForTicket(TicketId $ticketId): array
    {
        $models = TicketEscalationModel::query()
            ->select([
                'ticket_escalations.id',
                'ticket_escalations.ticket_id',
                'from_roles.name as from_role_name',
                'to_roles.name as to_role_name',
                'ticket_escalations.escalated_by',
                'ticket_escalations.escalated_at',
                'ticket_escalations.note',
            ])
            ->leftJoin('roles as from_roles', 'from_roles.id', '=', 'ticket_escalations.from_role_id')
            ->leftJoin('roles as to_roles', 'to_roles.id', '=', 'ticket_escalations.to_role_id')
            ->where('ticket_escalations.ticket_id', $ticketId->value())
            ->orderBy('ticket_escalations.escalated_at')
            ->get();

        $escalations = [];

        foreach ($models as $model) {
            $escalations[] = new TicketEscalation(
                (int) $model->getAttribute('id'),
                TicketId::fromInt((int) $model->getAttribute('ticket_id')),
                $model->getAttribute('from_role_name') !== null ? (string) $model->getAttribute('from_role_name') : null,
                $model->getAttribute('to_role_name') !== null ? (string) $model->getAttribute('to_role_name') : null,
                (int) $model->getAttribute('escalated_by'),
                $this->toImmutable($model->getAttribute('escalated_at')),
                $model->getAttribute('note') !== null ? (string) $model->getAttribute('note') : null,
            );
        }

        return $escalations;
    }

    private function roleIdByName(?string $roleName): ?int
    {
        if ($roleName === null) {
            return null;
        }

        $id = RoleModel::query()->where('name', $roleName)->value('id');

        return $id === null ? null : (int) $id;
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
