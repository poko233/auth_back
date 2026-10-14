<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use Lab\Domain\Ticketing\TicketId;
use Lab\Domain\Ticketing\TicketView;
use Lab\Domain\Ticketing\TicketViewRepository;
use Lab\Infrastructure\Persistence\Eloquent\Models\TicketViewModel;

/**
 * Implementación Eloquent del puerto de vistas.
 */
final class EloquentTicketViewRepository implements TicketViewRepository
{
    public function record(TicketView $view): void
    {
        $model = new TicketViewModel([
            'ticket_id' => $view->ticketId->value(),
            'user_id' => $view->userId,
            'viewed_at' => $view->viewedAt,
        ]);
        $model->save();
    }

    public function listForTicket(TicketId $ticketId): array
    {
        $models = TicketViewModel::query()
            ->select(['id', 'ticket_id', 'user_id', 'viewed_at'])
            ->where('ticket_id', $ticketId->value())
            ->orderBy('viewed_at', 'desc')
            ->get();

        $views = [];

        foreach ($models as $model) {
            $views[] = new TicketView(
                (int) $model->getAttribute('id'),
                TicketId::fromInt((int) $model->getAttribute('ticket_id')),
                (int) $model->getAttribute('user_id'),
                $model->getAttribute('viewed_at') instanceof \DateTimeInterface
                    ? \DateTimeImmutable::createFromInterface($model->getAttribute('viewed_at'))
                    : new \DateTimeImmutable((string) $model->getAttribute('viewed_at')),
            );
        }

        return $views;
    }
}
