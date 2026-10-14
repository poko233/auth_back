<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lab\Application\Authentication\AuthenticatedPrincipal;
use Lab\Application\Authentication\Basic\AuthenticateWithBasic;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Autenticación Basic (RFC 7617, header Authorization: Basic ...).
 *
 * Réplica exacta del patrón de ApiKeyAuthMiddleware: mismo body de 401,
 * mismo attribute de principal. El principal viene del header, nada más.
 */
final class BasicAuthMiddleware
{
    public const ATTRIBUTE = 'auth.principal';

    public function __construct(
        private AuthenticateWithBasic $authenticate,
    ) {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $principal = $this->principalFromHeader($request->header('Authorization', ''));

        if ($principal === null) {
            // TODO Fase 10: registrar intento fallido para métricas.
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $request->attributes->set(self::ATTRIBUTE, $principal);

        return $next($request);
    }

    private function principalFromHeader(string $header): ?AuthenticatedPrincipal
    {
        if (! str_starts_with($header, 'Basic ')) {
            return null;
        }

        $decoded = base64_decode(substr($header, 6), true);

        if ($decoded === false || ! str_contains($decoded, ':')) {
            return null;
        }

        [$email, $password] = explode(':', $decoded, 2);

        return $this->authenticate->execute($email, $password);
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
