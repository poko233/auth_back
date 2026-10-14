<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Lab\Application\Authentication\ApiKey\AuthenticateWithApiKey;
use Lab\Application\Authentication\ApiKey\IssueApiKey;
use Lab\Application\Authentication\ApiKey\ListApiKeys;
use Lab\Application\Authentication\ApiKey\RevokeApiKey;
use Lab\Application\Identity\FindUserById;
use Lab\Domain\Credential\ApiKey\ApiKeyId;
use Lab\Domain\Identity\UserId;
use Lab\Infrastructure\Http\ApiResources\ApiKeyResource;
use Lab\Infrastructure\Http\ApiResources\UserResource;
use Lab\Infrastructure\Http\Requests\IssueApiKeyRequest;
use Lab\Infrastructure\Http\Requests\LoginApiKeyRequest;

/**
 * Emisión, verificación y revocación de API Keys.
 *
 * La emisión queda abierta en Fase 6 (bootstrap de la primera key; las fases
 * siguientes la protegen). El plano se devuelve una sola vez.
 */
final class ApiKeyAuthController
{
    public function __construct(
        private AuthenticateWithApiKey $authenticate,
        private IssueApiKey $issue,
        private RevokeApiKey $revoke,
        private ListApiKeys $list,
        private FindUserById $findUser,
    ) {
    }

    public function login(LoginApiKeyRequest $request): JsonResponse
    {
        $principal = $this->authenticate->execute($request->validated()['key']);

        if ($principal === null) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $user = $this->findUser->execute($principal->userId);

        return response()->json([
            'user' => UserResource::make($user),
            'mechanism' => $principal->mechanism,
        ]);
    }

    public function issue(IssueApiKeyRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $issued = $this->issue->execute(
            (int) $validated['user_id'],
            $validated['label'] ?? null,
            isset($validated['expires_at']) ? new \DateTimeImmutable($validated['expires_at']) : null,
        );

        return response()->json([
            'key' => ApiKeyResource::make($issued['apiKey']),
            'plain' => $issued['plain'],
        ], 201);
    }

    public function revoke(string $id): JsonResponse
    {
        $this->revoke->execute(ApiKeyId::fromInt((int) $id));

        return response()->json(null, 204);
    }

    public function index(string $userId): JsonResponse
    {
        $keys = $this->list->execute((int) $userId);

        return response()->json(array_map(ApiKeyResource::make(...), $keys));
    }
}
