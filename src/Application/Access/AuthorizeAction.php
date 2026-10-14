<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use Lab\Application\Authentication\AuthenticatedPrincipal;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Access\RoleRepository;
use Lab\Domain\Identity\UserRepository;
use Lab\Domain\Shared\AuthorizationResult;
use Lab\Domain\Ticketing\Ticket;

/**
 * El corazón del experimento: autorización en dos capas.
 *
 * Capa 1 (RBAC): el permiso se resuelve contra RoleRepository::currentRolesOf(),
 * es decir, contra el estado ACTUAL en BD. Nunca del token, nunca de caché.
 * Capa 2 (ticket_rule): si la acción es sobre un ticket, se evalúan sus reglas
 * (borrado, CLOSED). El 403 indica failedLayer para segmentar métricas.
 */
final class AuthorizeAction
{
    public function __construct(
        private UserRepository $users,
        private RoleRepository $roles,
    ) {
    }

    public function execute(AuthenticatedPrincipal $principal, PermissionName $permission, ?Ticket $ticket = null): AuthorizationResult
    {
        $user = $this->users->findById($principal->userId);

        if ($user === null) {
            return AuthorizationResult::deniedByRbac('Identidad desconocida.');
        }

        if (! $user->isActive()) {
            return AuthorizationResult::deniedByRbac('Usuario inactivo.');
        }

        $granted = false;

        foreach ($this->roles->currentRolesOf($principal->userId) as $role) {
            if ($role->allows($permission)) {
                $granted = true;

                break;
            }
        }

        if (! $granted) {
            return AuthorizationResult::deniedByRbac("Sin permiso {$permission->value()}.");
        }

        if ($ticket !== null) {
            if ($ticket->isDeleted()) {
                return AuthorizationResult::deniedByTicketRule('Ticket eliminado.');
            }

            if ($permission->equals(PermissionName::fromString('DELETE')) && $ticket->isClosed()) {
                return AuthorizationResult::deniedByTicketRule('No se puede eliminar un ticket CLOSED.');
            }
        }

        return AuthorizationResult::allowed();
    }
}
