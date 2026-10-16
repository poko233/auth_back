<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lab\Application\Access\AuthorizeAction;
use Lab\Application\Authentication\ApiKey\AuthenticateWithApiKey;
use Lab\Application\Authentication\ApiKey\IssueApiKey;
use Lab\Application\Authentication\ApiKey\ListApiKeys;
use Lab\Application\Authentication\ApiKey\RevokeApiKey;
use Lab\Application\Identity\FindUserById;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Credential\ApiKey\ApiKeyId;
use Lab\Domain\Identity\UserId;
use Lab\Infrastructure\Http\ApiResources\ApiKeyResource;
use Lab\Infrastructure\Http\ApiResources\ForbiddenResource;
use Lab\Infrastructure\Http\ApiResources\UserResource;
use Lab\Infrastructure\Http\Middleware\ApiKeyAuthMiddleware;
use Lab\Infrastructure\Http\Requests\IssueApiKeyRequest;
use Lab\Infrastructure\Http\Requests\LoginApiKeyRequest;

/**
 * Emisión, verificación y revocación de API Keys.
 *
 * La gestión exige identidad válida y permiso ADMIN. El plano solo se entrega al emitir.
 */
final class ApiKeyAuthController
{
    public function __construct(
        private AuthenticateWithApiKey $authenticate,
        private IssueApiKey $issue,
        private RevokeApiKey $revoke,
        private ListApiKeys $list,
        private FindUserById $findUser,
        private AuthorizeAction $authorize,
    ) {}

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
        if ($denied = $this->denied($request)) {
            return $denied;
        }

        $validated = $request->validated();
        $issued = $this->issue->execute(
            (int) $validated['user_id'],
            $validated['label'] ?? null,
            isset($validated['expires_at'])
                ? (new \DateTimeImmutable($validated['expires_at']))->setTimezone(new \DateTimeZone('UTC'))
                : null,
        );

        return response()->json([
            'key' => ApiKeyResource::make($issued['apiKey']),
            'plain' => $issued['plain'],
        ], 201)->header('Cache-Control', 'no-store, private');
    }

    public function revoke(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->denied($request)) {
            return $denied;
        }

        if (! $this->revoke->execute(ApiKeyId::fromInt((int) $id))) {
            return response()->json(['error' => 'already_revoked', 'detail' => 'Esta API Key ya está revocada'], 409);
        }

        return response()->json(null, 204);
    }

    public function index(Request $request, string $userId): JsonResponse
    {
        if ($denied = $this->denied($request)) {
            return $denied;
        }

        $this->findUser->execute(UserId::fromString($userId));
        $keys = $this->list->execute((int) $userId);

        return response()->json(array_map(ApiKeyResource::make(...), $keys))->header('Cache-Control', 'no-store, private');
    }

    private function denied(Request $request): ?JsonResponse
    {
        $result = $this->authorize->execute(ApiKeyAuthMiddleware::principal($request), PermissionName::fromString('ADMIN'));

        if ($result->allowed) {
            return null;
        }

        $forbidden = ForbiddenResource::fromResult($result);

        return response()->json($forbidden['response'], $forbidden['status']);
    }
}
