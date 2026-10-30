<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lab\Application\Authentication\ApiKey\AuthenticateWithApiKey;
use Lab\Application\Authentication\AuthenticatedPrincipal;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Lab\Infrastructure\Http\Middleware\ApiKeyAuthMiddleware;

/**
 * Autenticación por API Key (header X-API-Key).
 *
 * El principal viene EXCLUSIVAMENTE del key: nada de headers X-User-Id.
 * Sin key válido responde 401. Punto.
 */
final class ApiKeyAuthMiddleware
{
    public const ATTRIBUTE = 'auth.principal';

    public function __construct(
        private AuthenticateWithApiKey $authenticate,
    ) {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainKey = $request->header('X-API-Key');

        if ($plainKey === null || trim($plainKey) === '') {
            // TODO Fase 10: registrar intento fallido (sin key) para métricas.
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $principal = $this->authenticate->execute($plainKey);

        if ($principal === null) {
            // TODO Fase 10: registrar intento fallido (key inválida/expirada/revocada) para métricas.
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $request->attributes->set(self::ATTRIBUTE, $principal);

        return $next($request);
    }

    public static function principal(Request $request): AuthenticatedPrincipal
    {
        $principal = $request->attributes->get(self::ATTRIBUTE);

        if (! $principal instanceof AuthenticatedPrincipal) {
            throw new UnauthorizedHttpException('', 'Sin principal autenticado.');
        }

        return $principal;
    }
    
}
