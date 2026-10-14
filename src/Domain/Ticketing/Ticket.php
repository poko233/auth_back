<?php

declare(strict_types=1);

namespace Lab\Domain\Ticketing;

use DateTimeImmutable;
use InvalidArgumentException;
use Lab\Domain\Ticketing\Exception\InvalidStatusTransition;

/**
 * Entidad Ticket rica para el experimento.
 *
 * Las reglas de ticket (estado, borrado) viven aquí y las evalúa
 * AuthorizeAction en la segunda capa: el 403 indica failedLayer para que el
 * experimento distinga RBAC de regla de ticket.
 *
 * createdBy/assignedTo son ids de usuario primitivos (int) para no acoplar
 * Ticketing con Identity: el ownership se resuelve en Application.
 */
final class Ticket
{
    /**
     * @var array<string, list<string>> Transiciones permitidas por estado.
     */
    private const TRANSITIONS = [
        'OPEN' => ['IN_PROGRESS', 'ESCALATED', 'CLOSED'],
        'IN_PROGRESS' => ['ESCALATED', 'RESOLVED', 'CLOSED'],
        'ESCALATED' => ['IN_PROGRESS', 'RESOLVED', 'CLOSED'],
        'RESOLVED' => ['IN_PROGRESS', 'CLOSED'],
        'CLOSED' => [],
    ];

    private function __construct(
        private ?TicketId $id,
        private string $title,
        private ?string $description,
        private TicketStatus $status,
        private TicketPriority $priority,
        private ?int $createdBy,
        private ?int $assignedTo,
        private ?string $assignedRole,
        private ?DateTimeImmutable $escalatedAt,
        private ?DateTimeImmutable $resolvedAt,
        private ?DateTimeImmutable $closedAt,
        private ?DateTimeImmutable $deletedAt,
    ) {
    }

    public static function create(string $title, ?string $description, ?int $createdBy, TicketPriority $priority = TicketPriority::MEDIUM): self
    {
        self::assertValidTitle($title);

        return new self(
            null,
            trim($title),
            $description,
            TicketStatus::OPEN,
            $priority,
            $createdBy,
            null,
            null,
            null,
            null,
            null,
            null,
        );
    }

    /**
     * Reconstruye un ticket persistido (usado por mappers y fakes).
     */
    public static function reconstitute(
        TicketId $id,
        string $title,
        ?string $description,
        TicketStatus $status,
        TicketPriority $priority,
        ?int $createdBy,
        ?int $assignedTo,
        ?string $assignedRole,
        ?DateTimeImmutable $escalatedAt,
        ?DateTimeImmutable $resolvedAt,
        ?DateTimeImmutable $closedAt,
        ?DateTimeImmutable $deletedAt,
    ): self {
        self::assertValidTitle($title);

        return new self(
            $id,
            trim($title),
            $description,
            $status,
            $priority,
            $createdBy,
            $assignedTo,
            $assignedRole,
            $escalatedAt,
            $resolvedAt,
            $closedAt,
            $deletedAt,
        );
    }

    private static function assertValidTitle(string $title): void
    {
        $trimmed = trim($title);

        if ($trimmed === '') {
            throw new InvalidArgumentException('El título del ticket no puede estar vacío.');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('El título del ticket no puede superar 255 caracteres.');
        }
    }

    public function id(): ?TicketId
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function status(): TicketStatus
    {
        return $this->status;
    }

    public function priority(): TicketPriority
    {
        return $this->priority;
    }

    public function createdBy(): ?int
    {
        return $this->createdBy;
    }

    public function assignedTo(): ?int
    {
        return $this->assignedTo;
    }

    public function assignedRole(): ?string
    {
        return $this->assignedRole;
    }

    public function escalatedAt(): ?DateTimeImmutable
    {
        return $this->escalatedAt;
    }

    public function resolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function closedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::CLOSED;
    }

    public function rename(string $title, ?string $description = null): void
    {
        self::assertValidTitle($title);

        $this->title = trim($title);
        $this->description = $description;
    }

    public function changePriority(TicketPriority $priority): void
    {
        $this->priority = $priority;
    }

    public function changeStatus(TicketStatus $to, DateTimeImmutable $now): void
    {
        if ($this->isDeleted()) {
            throw new Exception\InvalidStatusTransition('No se puede cambiar el estado de un ticket eliminado.');
        }

        $allowed = self::TRANSITIONS[$this->status->value] ?? [];

        if (! in_array($to->value, $allowed, true)) {
            throw new Exception\InvalidStatusTransition(
                "Transición inválida: {$this->status->value} → {$to->value}.",
            );
        }

        $this->status = $to;

        if ($to === TicketStatus::RESOLVED) {
            $this->resolvedAt = $now;
        }

        if ($to === TicketStatus::CLOSED) {
            $this->closedAt = $now;
        }
    }

    public function assignToUser(?int $userId): void
    {
        $this->assignedTo = $userId;
    }

    public function assignToRole(?string $roleName): void
    {
        $this->assignedRole = $roleName;
    }

    public function escalate(string $toRole, DateTimeImmutable $now): void
    {
        $this->changeStatus(TicketStatus::ESCALATED, $now);
        $this->assignedRole = $toRole;
        $this->escalatedAt = $now;
    }

    public function softDelete(DateTimeImmutable $now): void
    {
        $this->deletedAt = $now;
    }
}
