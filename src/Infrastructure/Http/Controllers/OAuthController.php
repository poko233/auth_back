<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Lab\Application\Authentication\OAuth\IntrospectToken;
use Lab\Application\Authentication\OAuth\IssueTokenByPassword;
use Lab\Application\Authentication\OAuth\RefreshAccessToken;
use Lab\Application\Authentication\OAuth\RevokeToken;
use Lab\Application\Identity\FindUserById;
use Lab\Domain\Credential\OAuth\Exception\InvalidGrant;
use Lab\Domain\Credential\OAuth\Exception\TokenAlreadyRevoked;
use Lab\Domain\Identity\UserId;
use Lab\Infrastructure\Http\ApiResources\UserResource;
use Lab\Infrastructure\Http\Requests\OAuthIntrospectRequest;
use Lab\Infrastructure\Http\Requests\OAuthRefreshRequest;
use Lab\Infrastructure\Http\Requests\OAuthRevokeRequest;
use Lab\Infrastructure\Http\Requests\OAuthTokenRequest;

/**
 * Servidor OAuth 2.0 propio, grant ROPC (solo laboratorio).
 *
 * El endpoint /token sigue RFC 6749 §5.2: grant inválido → 400
 * {error: invalid_grant}. El acceso a recursos sigue 401 unificado.
 */
final class OAuthController
{
    public function __construct(
        private IssueTokenByPassword $issue,
        private RefreshAccessToken $refresh,
        private IntrospectToken $introspect,
        private RevokeToken $revoke,
        private FindUserById $findUser,
    ) {
    }

    public function token(OAuthTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (($validated['grant_type'] ?? null) !== 'password') {
            return response()->json(['error' => 'unsupported_grant_type'], 400);
        }

        try {
            $issued = $this->issue->execute(
                $validated['client_id'],
                $validated['client_secret'],
                $validated['username'],
                $validated['password'],
            );
        } catch (InvalidGrant) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $issued['access_token'],
            'refresh_token' => $issued['refresh_token'],
            'expires_in' => $issued['expires_in'],
        ]);
    }

    public function refresh(OAuthRefreshRequest $request): JsonResponse
    {
        try {
            $issued = $this->refresh->execute($request->validated()['refresh_token']);
        } catch (InvalidGrant) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $issued['access_token'],
            'refresh_token' => $issued['refresh_token'],
            'expires_in' => $issued['expires_in'],
        ]);
    }

    public function introspect(OAuthIntrospectRequest $request): JsonResponse
    {
        $state = $this->introspect->execute($request->validated()['token']);

        if (! $state['active']) {
            return response()->json(['active' => false]);
        }

        return response()->json([
            'active' => true,
            'user' => UserResource::make($this->findUser->execute(UserId::fromString((string) $state['user_id']))),
            'scopes' => $state['scopes'],
            'exp' => $state['exp'],
        ]);
    }

    public function revoke(OAuthRevokeRequest $request): JsonResponse
    {
        try {
            $this->revoke->execute($request->validated()['token']);
        } catch (TokenAlreadyRevoked) {
            return response()->json([
                'error' => 'token_already_revoked',
                'detail' => 'El token ya estaba revocado',
            ], 409);
        } catch (InvalidGrant) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        return response()->json(null, 204);
    }
}
