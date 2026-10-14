<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\Jwt;

use Lab\Domain\Credential\Jwt\Exception\JwtInvalid;
use Lab\Domain\Credential\Jwt\JwtRevocationRepository;
use Lab\Domain\Credential\Jwt\JwtSigner;
use Lab\Domain\Shared\Clock;

/**
 * Rota un token válido: emite uno nuevo y revoca el jti anterior.
 *
 * Reuso-detection: si el token presentado ya está revocado, es señal de robo.
 * Se camina la cadena replaced_by y se revoca toda la familia viva, forzando
 * re-autenticación. El atacante queda fuera aunque tenga un token válido.
 */
final class RefreshJwt
{
    public function __construct(
        private JwtSigner $signer,
        private JwtRevocationRepository $revocations,
        private IssueJwt $issue,
        private Clock $clock,
    ) {
    }

    /**
     * @return array{token: string, jti: string, expires_at: string}
     */
    public function execute(string $token, int $ttlSeconds = 3600): array
    {
        try {
            $claims = $this->signer->verify($token);
        } catch (JwtInvalid $e) {
            throw $e;
        }

        if ($this->revocations->isRevoked($claims->jti)) {
            $this->revokeFamily($claims->jti);

            throw new JwtInvalid('Token ya usado: familia revocada por reuso.');
        }

        if ($claims->isExpired($this->clock->now())) {
            throw new JwtInvalid('Token expirado, no se puede refrescar.');
        }

        $issued = $this->issue->execute($claims->sub, $ttlSeconds);
        $this->revocations->recordReplacement($claims->jti, $issued['jti']);

        return $issued;
    }

    private function revokeFamily(string $jti): void
    {
        $current = $jti;
        $guard = 0;

        while ($current !== null && $guard < 100) {
            $next = $this->revocations->findReplacement($current);

            if ($next === null) {
                break;
            }

            $this->revocations->revoke($next);
            $current = $next;
            $guard++;
        }
    }
}
