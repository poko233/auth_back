<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Lab\Application\Authentication\Basic\AuthenticateWithBasic;
use Lab\Application\Authentication\Jwt\IssueJwt;
use Lab\Application\Authentication\Jwt\RefreshJwt;
use Lab\Application\Authentication\Jwt\RevokeJwt;
use Lab\Application\Identity\FindUserById;
use Lab\Domain\Credential\Jwt\Exception\JwtInvalid;
use Lab\Infrastructure\Http\ApiResources\UserResource;
use Lab\Infrastructure\Http\Requests\LoginJwtRequest;
use Lab\Infrastructure\Http\Requests\RefreshJwtRequest;
use Lab\Infrastructure\Http\Requests\RevokeJwtRequest;

/**
 * Emisión, refresco y revocación de JWT (solo laboratorio).
 *
 * Opción B: el login verifica identidad (email+password, igual que Basic y
 * OAuth ROPC). Los cuatro mecanismos quedan simétricos en emisión.
 */
final class JwtAuthController
{
    public function __construct(
        private AuthenticateWithBasic $basic,
        private IssueJwt $issue,
        private RefreshJwt $refresh,
        private RevokeJwt $revoke,
        private FindUserById $findUser,
    ) {
    }

    public function login(LoginJwtRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $principal = $this->basic->execute($validated['email'], $validated['password']);

        if ($principal === null) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $userId = (int) $principal->userId->value();
        $issued = $this->issue->execute($userId, (int) config('jwt.ttl', 3600));

        return response()->json([
            'user' => UserResource::make($this->findUser->execute($principal->userId)),
            'mechanism' => 'jwt',
            'token' => $issued['token'],
            'expires_at' => $issued['expires_at'],
        ], 201);
    }

    public function refresh(RefreshJwtRequest $request): JsonResponse
    {
        try {
            $issued = $this->refresh->execute($request->validated()['token'], (int) config('jwt.ttl', 3600));
        } catch (JwtInvalid) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        return response()->json([
            'token' => $issued['token'],
            'expires_at' => $issued['expires_at'],
        ]);
    }

    public function revoke(RevokeJwtRequest $request): JsonResponse
    {
        try {
            $this->revoke->execute($request->validated()['token']);
        } catch (JwtInvalid) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        return response()->json(null, 204);
    }
}
