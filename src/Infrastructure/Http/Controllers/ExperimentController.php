<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Lab\Application\Experiment\ChangeUserPrivileges;
use Lab\Application\Experiment\BeginExperiment;
use Lab\Application\Experiment\CreateExperiment;
use Lab\Application\Experiment\FinishExperiment;
use Lab\Application\Experiment\RecordEvent;
use Lab\Domain\Experiment\ExperimentStatus;
use Lab\Domain\Identity\UserId;
use Lab\Infrastructure\Http\ApiResources\ExperimentResource;
use Lab\Infrastructure\Http\Requests\ChangePrivilegesRequest;
use Lab\Infrastructure\Http\Requests\CreateExperimentRequest;
use Lab\Infrastructure\Http\Requests\FinishExperimentRequest;
use Lab\Infrastructure\Http\Middleware\ApiKeyAuthMiddleware;

/**
 * Ciclo de vida de experimentos + cambios de privilegios del escenario.
 */
final class ExperimentController
{
    public function __construct(
        private CreateExperiment $create,
        private BeginExperiment $begin,
        private FinishExperiment $finish,
        private RecordEvent $events,
        private ChangeUserPrivileges $privileges,
        private \Lab\Domain\Experiment\ExperimentRepository $experiments,
    ) {
    }

    public function index(): JsonResponse
    {
        $experiments = $this->experiments->all();

        return response()->json(array_map(ExperimentResource::make(...), $experiments));
    }

    public function store(CreateExperimentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $experiment = $this->create->execute(
            $validated['name'],
            $validated['mechanism'],
            UserId::fromString((string) $validated['test_user_id']),
            $validated['resources'],
        );

        return response()->json(ExperimentResource::make($experiment), 201);
    }

    public function start(string $id): JsonResponse
    {
        return response()->json(ExperimentResource::make($this->begin->execute((int) $id)));
    }

    public function finish(FinishExperimentRequest $request, string $id): JsonResponse
    {
        $status = ExperimentStatus::from($request->validated()['status'] ?? ExperimentStatus::COMPLETED->value);
        $experiment = $this->finish->execute((int) $id, $status);

        return response()->json(ExperimentResource::make($experiment));
    }

    public function privileges(ChangePrivilegesRequest $request, string $id): JsonResponse
{
    $validated = $request->validated();
    $this->privileges->execute(
        (int) $id,
        (int) $validated['user_id'],
        $validated['add_roles'] ?? [],
        $validated['remove_roles'] ?? [],
        (int) ApiKeyAuthMiddleware::principal($request)->userId->value(),
    );

    return response()->json(null, 204);
}

    public function events(string $id): JsonResponse
    {
        $events = $this->experiments->eventsFor((int) $id);

        return response()->json(array_map(
            static fn ($event): array => [
                'event_type' => $event->eventType,
                'payload' => $event->payload,
                'created_at' => $event->createdAt->format(DATE_ATOM),
            ],
            $events,
        ));
    }
}
