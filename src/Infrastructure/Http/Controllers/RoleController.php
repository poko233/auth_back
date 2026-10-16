<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Lab\Application\Access\AssignRoleToUser;
use Lab\Application\Access\CreateRole;
use Lab\Application\Access\DeleteRole;
use Lab\Application\Access\ListRoles;
use Lab\Application\Access\RevokeRoleFromUser;
use Lab\Application\Access\SyncRolePermissions;
use Lab\Application\Access\UpdateRole;
use Lab\Domain\Identity\UserId;
use Lab\Infrastructure\Http\ApiResources\RoleResource;
use Lab\Infrastructure\Http\Requests\AssignRoleRequest;
use Lab\Infrastructure\Http\Requests\StoreRoleRequest;
use Lab\Infrastructure\Http\Requests\SyncPermissionsRequest;
use Lab\Infrastructure\Http\Requests\UpdateRoleRequest;

/**
 * CRUD de roles + asignación a usuarios. Sin autenticación todavía (Fase 6+).
 */
final class RoleController
{
    public function __construct(
        private ListRoles $listRoles,
        private CreateRole $createRole,
        private UpdateRole $updateRole,
        private DeleteRole $deleteRole,
        private SyncRolePermissions $syncRolePermissions,
        private AssignRoleToUser $assignRoleToUser,
        private RevokeRoleFromUser $revokeRoleFromUser,
    ) {
    }

    public function index(): JsonResponse
    {
        $roles = $this->listRoles->execute();

        return response()->json(array_map(RoleResource::make(...), $roles));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $role = $this->createRole->execute(
            $validated['name'],
            $validated['description'] ?? null,
            $validated['permissions'] ?? [],
        );

        return response()->json(RoleResource::make($role), 201);
    }

    public function update(UpdateRoleRequest $request, string $name): JsonResponse
    {
        $role = $this->updateRole->execute($name, $request->validated()['description'] ?? null);

        return response()->json(RoleResource::make($role));
    }

    public function destroy(string $name): JsonResponse
    {
        $this->deleteRole->execute($name);

        return response()->json(null, 204);
    }

    public function syncPermissions(SyncPermissionsRequest $request, string $name): JsonResponse
    {
        $this->syncRolePermissions->execute($name, $request->validated()['permissions']);

        return response()->json(null, 204);
    }

    public function assign(AssignRoleRequest $request, string $userId): JsonResponse
    {
        $assigned = $this->assignRoleToUser->execute(UserId::fromString($userId), $request->validated()['role']);

        if (! $assigned) {
            return response()->json([
                'error' => 'role_already_assigned',
                'detail' => 'El usuario ya tiene este rol asignado',
            ], 409);
        }

        return response()->json(null, 204);
    }

    public function revoke(string $userId, string $roleName): JsonResponse
    {
        $this->revokeRoleFromUser->execute(UserId::fromString($userId), $roleName);

        return response()->json(null, 204);
    }
}
