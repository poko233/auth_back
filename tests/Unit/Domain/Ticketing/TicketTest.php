<?php

declare(strict_types=1);

use Lab\Domain\Ticketing\Exception\InvalidStatusTransition;
use Lab\Domain\Ticketing\Ticket;
use Lab\Domain\Ticketing\TicketPriority;
use Lab\Domain\Ticketing\TicketStatus;

/**
 * Tests de dominio del Ticket rico (sin Laravel, sin BD).
 */
it('crea un ticket en OPEN con prioridad por defecto', function (): void {
    $ticket = Ticket::create('Caída de red', 'Sin conexión en laboratorio', 7);

    expect($ticket->id())->toBeNull()
        ->and($ticket->title())->toBe('Caída de red')
        ->and($ticket->status())->toBe(TicketStatus::OPEN)
        ->and($ticket->priority())->toBe(TicketPriority::MEDIUM)
        ->and($ticket->createdBy())->toBe(7)
        ->and($ticket->isDeleted())->toBeFalse();
});

it('rechaza títulos vacíos y largos', function (): void {
    expect(fn () => Ticket::create('   ', null, null))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Ticket::create(str_repeat('a', 256), null, null))->toThrow(InvalidArgumentException::class);
});

it('permite transiciones válidas y actualiza marcas temporales', function (): void {
    $ticket = Ticket::create('Falla', null, null);
    $now = new DateTimeImmutable();

    $ticket->changeStatus(TicketStatus::IN_PROGRESS, $now);

    expect($ticket->status())->toBe(TicketStatus::IN_PROGRESS);

    $ticket->changeStatus(TicketStatus::RESOLVED, $now);

    expect($ticket->resolvedAt())->not->toBeNull();

    $ticket->changeStatus(TicketStatus::CLOSED, $now);

    expect($ticket->isClosed())->toBeTrue()
        ->and($ticket->closedAt())->not->toBeNull();
});

it('rechaza transiciones inválidas y cambios sobre borrados', function (): void {
    $ticket = Ticket::create('Falla', null, null);
    $now = new DateTimeImmutable();

    expect(fn () => $ticket->changeStatus(TicketStatus::RESOLVED, $now))
        ->toThrow(InvalidStatusTransition::class);

    $ticket->softDelete($now);

    expect($ticket->isDeleted())->toBeTrue()
        ->and(fn () => $ticket->changeStatus(TicketStatus::IN_PROGRESS, $now))
        ->toThrow(InvalidStatusTransition::class);
});

it('asigna usuario y rol, y escala con estado y marca', function (): void {
    $ticket = Ticket::create('Falla', null, null);
    $now = new DateTimeImmutable();

    $ticket->assignToUser(9);
    $ticket->changeStatus(TicketStatus::IN_PROGRESS, $now);
    $ticket->escalate('ADMIN', $now);

    expect($ticket->status())->toBe(TicketStatus::ESCALATED)
        ->and($ticket->assignedRole())->toBe('ADMIN')
        ->and($ticket->escalatedAt())->not->toBeNull();
});

it('TicketId compara por valor y rechaza vacíos', function (): void {
    expect(Lab\Domain\Ticketing\TicketId::fromInt(1)->equals(Lab\Domain\Ticketing\TicketId::fromInt(1)))->toBeTrue()
        ->and(Lab\Domain\Ticketing\TicketId::fromInt(1)->equals(Lab\Domain\Ticketing\TicketId::fromInt(2)))->toBeFalse()
        ->and(fn () => Lab\Domain\Ticketing\TicketId::fromInt(0))->toThrow(InvalidArgumentException::class);
});
