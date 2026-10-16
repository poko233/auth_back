<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Lab\Application\Access\ListUserRoles;
use Lab\Application\Identity\ChangeUserPassword;
use Lab\Application\Identity\DeactivateUser;
use Lab\Application\Identity\FindUserById;
use Lab\Application\Identity\ListUsers;
use Lab\Application\Identity\RegisterUser;
use Lab\Application\Identity\UpdateUser;
use Lab\Domain\Identity\UserId;
use Lab\Infrastructure\Http\ApiResources\RoleResource;
use Lab\Infrastructure\Http\ApiResources\UserResource;
use Lab\Infrastructure\Http\Requests\ChangePasswordRequest;
use Lab\Infrastructure\Http\Requests\StoreUserRequest;
use Lab\Infrastructure\Http\Requests\UpdateUserRequest;

/**
 * CRUD de usuarios. Sin autenticación todavía (Fase 6+).
 */
final class UserController
{
    public function __construct(
        private RegisterUser $registerUser,
        private FindUserById $findUserById,
        private ListUsers $listUsers,
        private UpdateUser $updateUser,
        private DeactivateUser $deactivateUser,
        private ChangeUserPassword $changeUserPassword,
        private ListUserRoles $listUserRoles,
    ) {
    }

    public function index(): JsonResponse
    {
        $users = $this->listUsers->execute();

        return response()->json(array_map(UserResource::make(...), $users));
    }

    public function show(string $id): JsonResponse
    {
        $user = $this->findUserById->execute(UserId::fromString($id));

        return response()->json([
            ...UserResource::make($user),
            'roles' => array_map(RoleResource::make(...), $this->listUserRoles->execute($user->id())),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $this->registerUser->execute($validated['name'], $validated['email'], $validated['password']);

        return response()->json(UserResource::make($user), 201);
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();
        $user = $this->updateUser->execute(
            UserId::fromString($id),
            $validated['name'] ?? null,
            $validated['email'] ?? null,
        );

        return response()->json(UserResource::make($user));
    }

    public function deactivate(string $id): JsonResponse
    {
        $user = $this->deactivateUser->execute(UserId::fromString($id));

        return response()->json(UserResource::make($user));
    }

    public function changePassword(ChangePasswordRequest $request, string $id): JsonResponse
    {
        $user = $this->changeUserPassword->execute(UserId::fromString($id), $request->validated()['password']);

        return response()->json(UserResource::make($user));
    }
}
