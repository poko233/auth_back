<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lab\Application\Authentication\AuthenticatedPrincipal;
use Lab\Application\Authentication\Jwt\VerifyJwt;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Autenticación Bearer JWT (RFC 7519).
 *
 * Réplica exacta del patrón API Key/Basic: mismo 401 bare, mismo attribute.
 * La ambigüedad Bearer JWT-vs-OAuth se resuelve en Fase 9 con
 * X-Auth-Mechanism; hasta entonces Bearer siempre es JWT (documentado).
 */
final class JwtAuthMiddleware
{
    public const ATTRIBUTE = 'auth.principal';

    public function __construct(
        private VerifyJwt $verify,
    ) {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            // TODO Fase 10: registrar intento fallido para métricas.
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $principal = $this->verify->execute(substr($header, 7));

        if ($principal === null) {
            // TODO Fase 10: registrar intento fallido para métricas.
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
