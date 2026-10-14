<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lab\Application\Access\AuthorizeAction;
use Lab\Application\Ticketing\GetTicketEscalations;
use Lab\Application\Ticketing\GetTicketHistory;
use Lab\Application\Ticketing\GetTicketViews;
use Lab\Application\Ticketing\ShowTicket;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Ticketing\TicketId;
use Lab\Infrastructure\Http\ApiResources\ForbiddenResource;
use Lab\Infrastructure\Http\ApiResources\TicketEscalationResource;
use Lab\Infrastructure\Http\ApiResources\TicketHistoryResource;
use Lab\Infrastructure\Http\ApiResources\TicketViewResource;
use Lab\Infrastructure\Http\Middleware\ApiKeyAuthMiddleware;

/**
 * Tracking de tickets protegido por API Key (lecturas exigen READ).
 */
final class TicketHistoryController
{
    public function __construct(
        private ShowTicket $showTicket,
        private GetTicketHistory $getTicketHistory,
        private GetTicketViews $getTicketViews,
        private GetTicketEscalations $getTicketEscalations,
        private AuthorizeAction $authorize,
    ) {
    }

    private function checkRead(Request $request, int $id): ?JsonResponse
    {
        $principal = ApiKeyAuthMiddleware::principal($request);
        $ticket = $this->showTicket->execute(TicketId::fromInt($id));
        $result = $this->authorize->execute($principal, PermissionName::fromString('READ'), $ticket);

        if (! $result->allowed) {
            $payload = ForbiddenResource::fromResult($result);

            return response()->json($payload['response'], $payload['status']);
        }

        return null;
    }

    public function history(Request $request, int $id): JsonResponse
    {
        if (($denied = $this->checkRead($request, $id)) !== null) {
            return $denied;
        }

        $entries = $this->getTicketHistory->execute(TicketId::fromInt($id));

        return response()->json(array_map(TicketHistoryResource::make(...), $entries));
    }

    public function views(Request $request, int $id): JsonResponse
    {
        if (($denied = $this->checkRead($request, $id)) !== null) {
            return $denied;
        }

        $views = $this->getTicketViews->execute(TicketId::fromInt($id));

        return response()->json(array_map(TicketViewResource::make(...), $views));
    }

    public function escalations(Request $request, int $id): JsonResponse
    {
        if (($denied = $this->checkRead($request, $id)) !== null) {
            return $denied;
        }

        $escalations = $this->getTicketEscalations->execute(TicketId::fromInt($id));

        return response()->json(array_map(TicketEscalationResource::make(...), $escalations));
    }
}
