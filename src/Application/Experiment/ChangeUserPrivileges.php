<?php

declare(strict_types=1);

namespace Lab\Application\Experiment;

use Lab\Application\Access\AssignRoleToUser;
use Lab\Application\Access\RevokeRoleFromUser;
use Lab\Domain\Identity\UserId;

/**
 * Cambia privilegios dentro de un experimento y deja evento role_change.
 *
 * Es el paso "escalado/reducción" de la secuencia experimental.
 */
final class ChangeUserPrivileges
{
    public function __construct(
        private AssignRoleToUser $assign,
        private RevokeRoleFromUser $revoke,
        private RecordEvent $events,
    ) {
    }

    /**
     * @param list<string> $addRoles
     * @param list<string> $removeRoles
     */
    public function execute(int $experimentId, int $userId, array $addRoles = [], array $removeRoles = []): void
    {
        $id = UserId::fromString((string) $userId);

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
        ]);
    }
}
