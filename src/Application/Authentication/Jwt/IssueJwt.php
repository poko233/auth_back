<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\Jwt;

use Lab\Domain\Credential\Jwt\JwtSigner;
use Lab\Domain\Identity\Exception\UserNotFound;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Identity\UserRepository;
use Lab\Domain\Shared\Clock;

/**
 * Emite un JWT para un usuario activo.
 */
final class IssueJwt
{
    public function __construct(
        private JwtSigner $signer,
        private UserRepository $users,
        private Clock $clock,
    ) {
    }

    /**
     * @return array{token: string, jti: string, expires_at: string}
     */
    public function execute(int $userId, int $ttlSeconds = 3600): array
    {
        $user = $this->users->findById(UserId::fromString((string) $userId));

        if ($user === null) {
            throw new UserNotFound("Usuario no encontrado: {$userId}.");
        }

        if (! $user->isActive()) {
            throw new UserNotFound("Usuario inactivo: {$userId}.");
        }

        $jti = bin2hex(random_bytes(16));
        $token = $this->signer->sign($userId, $jti, $ttlSeconds);

        return [
            'token' => $token,
            'jti' => $jti,
            'expires_at' => $this->clock->now()->modify("+{$ttlSeconds} seconds")->format(DATE_ATOM),
        ];
    }
}
