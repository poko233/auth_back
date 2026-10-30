<?php

declare(strict_types=1);

namespace Lab\Application\Experiment;

use Lab\Application\Identity\FindUserById;
use Lab\Domain\Experiment\Exception\ExperimentNotFound;
use Lab\Domain\Experiment\Exception\ExperimentStateConflict;
use Lab\Domain\Experiment\ExperimentRepository;
use Lab\Domain\Experiment\ExperimentStatus;
use Lab\Domain\Shared\TransactionManager;
use Lab\Domain\Experiment\MetricRepository;

public function __construct(
    private AssignRoleToUser $assign,
    private RevokeRoleFromUser $revoke,
    private RecordEvent $events,
    private ExperimentRepository $experiments,
    private FindUserById $users,
    private TransactionManager $transactions,
    private MetricRepository $metrics,
) {
}

public function execute(int $experimentId, int $userId, array $addRoles = [], array $removeRoles = [], ?int $adminId = null): void
{
    $experiment = $this->experiments->findById($experimentId)
        ?? throw new ExperimentNotFound("Experimento no encontrado: {$experimentId}.");

    if ($experiment->status() !== ExperimentStatus::RUNNING) {
        throw new ExperimentStateConflict('experiment_not_running', 'Solo se puede cambiar el rol en un experimento en ejecución');
    }

    $adminName = $adminId === null ? null : $this->users->execute(UserId::fromString((string) $adminId))->name();
    $id = UserId::fromString((string) $userId);

    $this->transactions->transaction(function () use ($experimentId, $userId, $id, $addRoles, $removeRoles, $adminId, $adminName): void {
        foreach ($addRoles as $role) {
            $this->assign->execute($id, $role);
        }

        foreach ($removeRoles as $role) {
            $this->revoke->execute($id, $role);
        }

        $this->events->execute($experimentId, 'role_change', [
            'user_id' => $userId,
            'added' => array_values($addRoles),
            'removed' => array_values($removeRoles),
            'admin_id' => $adminId,
            'admin_name' => $adminName,
            'after_metric_id' => $lastMetricId,
        ]);

        $lastMetricId = 0;
        foreach ($this->metrics->listFor($experimentId) as $metric) {
        $lastMetricId = max($lastMetricId, $metric->id ?? 0);
        }
    });
}