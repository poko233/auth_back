<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lab\Application\Authentication\ApiKey\AuthenticateWithApiKey;
use Lab\Application\Authentication\Basic\AuthenticateWithBasic;
use Lab\Application\Authentication\Jwt\VerifyJwt;
use Lab\Application\Authentication\OAuth\VerifyOAuth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auth multimecanismo para endpoints del experimento.
 *
 * Reglas cerradas (verificación Fase 7, extendidas en Fase 9):
 * 1. Orden fijo por header presente: X-API-Key → Basic → Bearer.
 *    Con Bearer, X-Auth-Mechanism discrimina (jwt|oauth); ausente → JWT.
 *    Si hay dos mecanismos, gana el primero en el orden. Determinista.
 * 2. Sin fallback: si el header presente falla, 401 inmediato. El orden es
 *    "el primer header presente", no "el primero que valide".
 * 3. El mechanism del principal lo pone el middleware que autenticó, nunca el
 *    cliente. En Fase 10 cada métrica se atribuye a ese mechanism.
 * 4. 401 siempre bare: {"error": "unauthenticated"}. Sin detail hacia afuera
 *    (no filtrar información); el detalle va al log, no al cliente.
 */
final class FlexibleAuthMiddleware
{
    public function __construct(
        private AuthenticateWithApiKey $apiKey,
        private AuthenticateWithBasic $basic,
        private VerifyJwt $jwt,
        private VerifyOAuth $oauth,
    ) {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $principal = null;
        $authStart = microtime(true);

        if ($request->header('X-API-Key') !== null) {
            $principal = $this->apiKey->execute($request->header('X-API-Key', ''));
        } elseif (str_starts_with($request->header('Authorization', ''), 'Basic ')) {
            $decoded = base64_decode(substr($request->header('Authorization', ''), 6), true);

            if ($decoded !== false && str_contains($decoded, ':')) {
                [$email, $password] = explode(':', $decoded, 2);
                $principal = $this->basic->execute($email, $password);
            }
        } elseif (str_starts_with($request->header('Authorization', ''), 'Bearer ')) {
            // X-Auth-Mechanism (Fase 9): jwt|oauth explícito; ausente → JWT
            // (backward compat); desconocido → 400 (problema del cliente).
            $mechanism = strtolower(trim($request->header('X-Auth-Mechanism', '')));
            $token = substr($request->header('Authorization', ''), 7);

            if ($mechanism === '' || $mechanism === 'jwt') {
                $principal = $this->jwt->execute($token);
            } elseif ($mechanism === 'oauth') {
                $principal = $this->oauth->execute($token);
            } else {
                return response()->json(['error' => 'invalid_mechanism'], 400);
            }
        }

        // Tiempo de auth para métricas (Fase 10): el recorder lo lee.
        $request->attributes->set('auth.ms', (int) round((microtime(true) - $authStart) * 1000));

        if ($principal === null) {
            // TODO Fase 10: registrar intento fallido para métricas.
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $request->attributes->set(ApiKeyAuthMiddleware::ATTRIBUTE, $principal);

        return $next($request);
    }

}
