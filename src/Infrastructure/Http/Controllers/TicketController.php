<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lab\Application\Access\AuthorizeAction;
use Lab\Application\Ticketing\AssignTicketToRole;
use Lab\Application\Ticketing\AssignTicketToUser;
use Lab\Application\Ticketing\ChangeTicketStatus;
use Lab\Application\Ticketing\CreateTicket;
use Lab\Application\Ticketing\DeleteTicket;
use Lab\Application\Ticketing\EscalateTicket;
use Lab\Application\Ticketing\ListTickets;
use Lab\Application\Ticketing\RecordTicketView;
use Lab\Application\Ticketing\ShowTicket;
use Lab\Application\Ticketing\UpdateTicket;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Shared\AuthorizationResult;
use Lab\Domain\Ticketing\TicketId;
use Lab\Domain\Ticketing\TicketPriority;
use Lab\Domain\Ticketing\TicketStatus;
use Lab\Infrastructure\Http\ApiResources\ForbiddenResource;
use Lab\Infrastructure\Http\ApiResources\TicketResource;
use Lab\Infrastructure\Http\Middleware\ApiKeyAuthMiddleware;
use Lab\Infrastructure\Http\Requests\AssignTicketRequest;
use Lab\Infrastructure\Http\Requests\ChangeTicketStatusRequest;
use Lab\Infrastructure\Http\Requests\EscalateTicketRequest;
use Lab\Infrastructure\Http\Requests\StoreTicketRequest;
use Lab\Infrastructure\Http\Requests\UpdateTicketRequest;
use Throwable;

/**
 * Tickets protegidos por API Key.
 *
 * Regla nueva de Fase 6: toda ruta pasa por el middleware; el principal viene
 * del key. Orden: existencia (404) → autorización (403 con layer).
 */
final class TicketController
{
    public function __construct(
        private CreateTicket $createTicket,
        private ListTickets $listTickets,
        private ShowTicket $showTicket,
        private UpdateTicket $updateTicket,
        private DeleteTicket $deleteTicket,
        private AssignTicketToUser $assignTicketToUser,
        private AssignTicketToRole $assignTicketToRole,
        private EscalateTicket $escalateTicket,
        private ChangeTicketStatus $changeTicketStatus,
        private RecordTicketView $recordTicketView,
        private AuthorizeAction $authorize,
    ) {
    }

    private function deny(AuthorizationResult $result): JsonResponse
    {
        $payload = ForbiddenResource::fromResult($result);

        return response()->json($payload['response'], $payload['status']);
    }

    private function actorId(Request $request): int
    {
        return (int) ApiKeyAuthMiddleware::principal($request)->userId->value();
    }

    public function index(Request $request): JsonResponse
    {
        $principal = ApiKeyAuthMiddleware::principal($request);
        $result = $this->authorize->execute($principal, PermissionName::fromString('READ'));

        if (! $result->allowed) {
            return $this->deny($result);
        }

        $tickets = $this->listTickets->execute();

        return response()->json(array_map(TicketResource::make(...), $tickets));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $principal = ApiKeyAuthMiddleware::principal($request);
        $ticket = $this->showTicket->execute(TicketId::fromInt($id));

        $result = $this->authorize->execute($principal, PermissionName::fromString('READ'), $ticket);

        if (! $result->allowed) {
            return $this->deny($result);
        }

        try {
            $this->recordTicketView->execute($ticket->id(), $this->actorId($request));
        } catch (Throwable $e) {
            Log::warning('No se pudo registrar la vista del ticket.', ['ticket_id' => $id, 'error' => $e->getMessage()]);
        }

        return response()->json(TicketResource::make($ticket));
    }

    public function store(Request $request, StoreTicketRequest $ticketRequest): JsonResponse
    {
        $principal = ApiKeyAuthMiddleware::principal($request);
        $result = $this->authorize->execute($principal, PermissionName::fromString('WRITE'));

        if (! $result->allowed) {
            return $this->deny($result);
        }

        $validated = $ticketRequest->validated();
        $ticket = $this->createTicket->execute(
            $validated['title'],
            $validated['description'] ?? null,
            $this->actorId($request),
            isset($validated['priority']) ? TicketPriority::from($validated['priority']) : TicketPriority::MEDIUM,
        );

        return response()->json(TicketResource::make($ticket), 201);
    }

    public function update(Request $request, UpdateTicketRequest $ticketRequest, int $id): JsonResponse
    {
        $principal = ApiKeyAuthMiddleware::principal($request);
        $ticket = $this->showTicket->execute(TicketId::fromInt($id));

        $result = $this->authorize->execute($principal, PermissionName::fromString('WRITE'), $ticket);

        if (! $result->allowed) {
            return $this->deny($result);
        }

        $validated = $ticketRequest->validated();
        $updated = $this->updateTicket->execute(
            $ticket->id(),
            $validated['title'] ?? null,
            $validated['description'] ?? null,
            isset($validated['priority']) ? TicketPriority::from($validated['priority']) : null,
            $this->actorId($request),
        );

        return response()->json(TicketResource::make($updated));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $principal = ApiKeyAuthMiddleware::principal($request);
        $ticket = $this->showTicket->execute(TicketId::fromInt($id));

        $result = $this->authorize->execute($principal, PermissionName::fromString('DELETE'), $ticket);

        if (! $result->allowed) {
            return $this->deny($result);
        }

        $this->deleteTicket->execute($ticket->id(), $this->actorId($request));

        return response()->json(null, 204);
    }

    public function assign(Request $request, AssignTicketRequest $assignRequest, int $id): JsonResponse
    {
        $principal = ApiKeyAuthMiddleware::principal($request);
        $ticket = $this->showTicket->execute(TicketId::fromInt($id));

        $result = $this->authorize->execute($principal, PermissionName::fromString('WRITE'), $ticket);

        if (! $result->allowed) {
            return $this->deny($result);
        }

        $validated = $assignRequest->validated();

        if (array_key_exists('user_id', $validated)) {
            $updated = $this->assignTicketToUser->execute($ticket->id(), $validated['user_id'], $this->actorId($request));
        } else {
            $updated = $this->assignTicketToRole->execute($ticket->id(), $validated['role'], $this->actorId($request));
        }

        return response()->json(TicketResource::make($updated));
    }

    public function escalate(Request $request, EscalateTicketRequest $escalateRequest, int $id): JsonResponse
    {
        $principal = ApiKeyAuthMiddleware::principal($request);
        $ticket = $this->showTicket->execute(TicketId::fromInt($id));

        $result = $this->authorize->execute($principal, PermissionName::fromString('WRITE'), $ticket);

        if (! $result->allowed) {
            return $this->deny($result);
        }

        $validated = $escalateRequest->validated();
        $updated = $this->escalateTicket->execute($ticket->id(), $validated['to_role'], $this->actorId($request), $validated['note'] ?? null);

        return response()->json(TicketResource::make($updated));
    }

    public function changeStatus(Request $request, ChangeTicketStatusRequest $statusRequest, int $id): JsonResponse
    {
        $principal = ApiKeyAuthMiddleware::principal($request);
        $ticket = $this->showTicket->execute(TicketId::fromInt($id));

        $result = $this->authorize->execute($principal, PermissionName::fromString('WRITE'), $ticket);

        if (! $result->allowed) {
            return $this->deny($result);
        }

        $updated = $this->changeTicketStatus->execute(
            $ticket->id(),
            TicketStatus::from($statusRequest->validated()['status']),
            $this->actorId($request),
        );

        return response()->json(TicketResource::make($updated));
    }
}
