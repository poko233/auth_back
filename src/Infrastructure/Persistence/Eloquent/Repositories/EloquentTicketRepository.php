<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use Lab\Domain\Ticketing\Ticket;
use Lab\Domain\Ticketing\TicketId;
use Lab\Domain\Ticketing\TicketRepository;
use Lab\Infrastructure\Persistence\Eloquent\Mappers\TicketMapper;
use Lab\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Lab\Infrastructure\Persistence\Eloquent\Models\TicketModel;

/**
 * Implementación Eloquent del puerto de tickets.
 *
 * El nombre del rol asignado llega por LEFT JOIN (una sola query, sin N+1).
 * findById excluye soft-deleted: un ticket eliminado responde como no encontrado.
 */
final class EloquentTicketRepository implements TicketRepository
{
    public function __construct(
        private TicketMapper $mapper,
    ) {
    }

    private function baseQuery(bool $withDeleted): \Illuminate\Database\Eloquent\Builder
    {
        $query = TicketModel::query()
            ->select([
                'tickets.id',
                'tickets.title',
                'tickets.description',
                'tickets.status',
                'tickets.priority',
                'tickets.created_by',
                'tickets.assigned_to',
                'tickets.assigned_role_id',
                'tickets.escalated_at',
                'tickets.resolved_at',
                'tickets.closed_at',
                'tickets.deleted_at',
                'tickets.created_at',
                'tickets.updated_at',
                'roles.name as assigned_role_name',
            ])
            ->leftJoin('roles', 'roles.id', '=', 'tickets.assigned_role_id');

        if (! $withDeleted) {
            $query->whereNull('tickets.deleted_at');
        }

        return $query;
    }

    private function roleIdByName(?string $roleName): ?int
    {
        if ($roleName === null) {
            return null;
        }

        $id = RoleModel::query()->where('name', $roleName)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function save(Ticket $ticket): Ticket
    {
        $attributes = [
            'title' => $ticket->title(),
            'description' => $ticket->description(),
            'status' => $ticket->status()->value,
            'priority' => $ticket->priority()->value,
            'created_by' => $ticket->createdBy(),
            'assigned_to' => $ticket->assignedTo(),
            'assigned_role_id' => $this->roleIdByName($ticket->assignedRole()),
            'escalated_at' => $ticket->escalatedAt(),
            'resolved_at' => $ticket->resolvedAt(),
            'closed_at' => $ticket->closedAt(),
            'deleted_at' => $ticket->deletedAt(),
        ];

        if ($ticket->id() !== null) {
            $model = TicketModel::withTrashed()->where('id', $ticket->id()->value())->first();

            if ($model !== null) {
                $model->fill($attributes);
                $model->save();

                return $this->findByIdWithTrashed($ticket->id());
            }
        }

        $model = new TicketModel($attributes);
        $model->save();

        return $this->findByIdWithTrashed(TicketId::fromInt((int) $model->getAttribute('id')));
    }

    private function findByIdWithTrashed(TicketId $id): Ticket
    {
        /** @var TicketModel $model */
        $model = $this->baseQuery(true)->where('tickets.id', $id->value())->firstOrFail();

        return $this->mapper->toDomain($model);
    }

    public function findById(TicketId $id): ?Ticket
    {
        $model = $this->baseQuery(false)->where('tickets.id', $id->value())->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function listAll(bool $withDeleted = false): array
    {
        $models = $this->baseQuery($withDeleted)->orderBy('tickets.id')->get();
        $tickets = [];

        foreach ($models as $model) {
            $tickets[] = $this->mapper->toDomain($model);
        }

        return $tickets;
    }

    public function delete(Ticket $ticket): void
    {
        if ($ticket->id() === null) {
            return;
        }

        TicketModel::query()->where('id', $ticket->id()->value())->delete();
    }
}
