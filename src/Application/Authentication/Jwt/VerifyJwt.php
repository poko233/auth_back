<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\Jwt;

use Lab\Application\Authentication\AuthenticatedPrincipal;
use Lab\Domain\Credential\Jwt\Exception\JwtInvalid;
use Lab\Domain\Credential\Jwt\JwtRevocationRepository;
use Lab\Domain\Credential\Jwt\JwtSigner;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Shared\Clock;

/**
 * Verifica un Bearer JWT: firma → vigencia → revocación → principal.
 *
 * Decisión experimental (opción C): consulta jwt_revocations en CADA
 * verificación (jti con índice UNIQUE, O(1)). Cuesta un round-trip a la BD
 * (~150-250 ms contra Supabase) y eso aplana la ventaja criptográfica de JWT
 * frente a API Key. Se acepta por seguridad (revocación real) y se documenta
 * en la metodología: las comparaciones de latencia son relativas e incluyen
 * este coste. OAuth replica el mismo patrón.
 * Coste total: exactamente 1 query (firma cripto + isRevoked).
 */
final class VerifyJwt
{
    public function __construct(
        private JwtSigner $signer,
        private JwtRevocationRepository $revocations,
        private Clock $clock,
    ) {
    }

    public function execute(string $token): ?AuthenticatedPrincipal
    {
        if (trim($token) === '') {
            return null;
        }

        try {
            $claims = $this->signer->verify($token);
        } catch (JwtInvalid) {
            return null;
        }

        if ($claims->isExpired($this->clock->now())) {
            return null;
        }

        if ($this->revocations->isRevoked($claims->jti)) {
            return null;
        }

        return new AuthenticatedPrincipal(
            UserId::fromString((string) $claims->sub),
            'jwt',
            ['jti' => $claims->jti, 'exp' => $claims->exp->format(DATE_ATOM)],
        );
    }
}
