<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Lab\Application\Authentication\Basic\AuthenticateWithBasic;
use Lab\Application\Identity\FindUserById;
use Lab\Infrastructure\Http\ApiResources\UserResource;
use Lab\Infrastructure\Http\Requests\LoginBasicRequest;

/**
 * Verificación de credenciales Basic (solo laboratorio: el uso real es el
 * header directo sobre endpoints protegidos).
 */
final class BasicAuthController
{
    public function __construct(
        private AuthenticateWithBasic $authenticate,
        private FindUserById $findUser,
    ) {
    }

    public function login(LoginBasicRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $principal = $this->authenticate->execute($validated['email'], $validated['password']);

        if ($principal === null) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        return response()->json([
            'user' => UserResource::make($this->findUser->execute($principal->userId)),
            'mechanism' => $principal->mechanism,
        ]);
    }
}
