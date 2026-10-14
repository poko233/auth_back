<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\Jwt;

use Lab\Domain\Credential\Jwt\Exception\JwtInvalid;
use Lab\Domain\Credential\Jwt\JwtRevocationRepository;
use Lab\Domain\Credential\Jwt\JwtSigner;

/**
 * Revoca un token por su jti (verifica firma, ignora expiración).
 */
final class RevokeJwt
{
    public function __construct(
        private JwtSigner $signer,
        private JwtRevocationRepository $revocations,
    ) {
    }

    public function execute(string $token): void
    {
        try {
            $claims = $this->signer->parse($token);
        } catch (JwtInvalid $e) {
            throw $e;
        }

        $this->revocations->revoke($claims->jti);
    }
}
