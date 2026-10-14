<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Lab\Application\Access\CreatePermission;
use Lab\Application\Access\DeletePermission;
use Lab\Application\Access\ListPermissions;
use Lab\Application\Access\UpdatePermission;
use Lab\Infrastructure\Http\ApiResources\PermissionResource;
use Lab\Infrastructure\Http\Requests\StorePermissionRequest;
use Lab\Infrastructure\Http\Requests\UpdatePermissionRequest;

/**
 * CRUD de permisos dinámicos. Sin autenticación todavía (Fase 6+).
 */
final class PermissionController
{
    public function __construct(
        private ListPermissions $listPermissions,
        private CreatePermission $createPermission,
        private UpdatePermission $updatePermission,
        private DeletePermission $deletePermission,
    ) {
    }

    public function index(): JsonResponse
    {
        $permissions = $this->listPermissions->execute();

        return response()->json(array_map(PermissionResource::make(...), $permissions));
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $permission = $this->createPermission->execute($validated['name'], $validated['description'] ?? null);

        return response()->json(PermissionResource::make($permission), 201);
    }

    public function update(UpdatePermissionRequest $request, string $name): JsonResponse
    {
        $permission = $this->updatePermission->execute($name, $request->validated()['description'] ?? null);

        return response()->json(PermissionResource::make($permission));
    }

    public function destroy(string $name): JsonResponse
    {
        $this->deletePermission->execute($name);

        return response()->json(null, 204);
    }
}
