<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use Lab\Domain\Credential\Jwt\JwtRevocationRepository;
use Lab\Infrastructure\Persistence\Eloquent\Models\JwtRevocationModel;

/**
 * Implementación Eloquent de la denylist de JTIs.
 */
final class EloquentJwtRevocationRepository implements JwtRevocationRepository
{
    public function revoke(string $jti): void
    {
        JwtRevocationModel::query()->updateOrCreate(
            ['jti' => $jti],
            ['expires_at' => now()->addDay(), 'revoked_at' => now()],
        );
    }

    public function isRevoked(string $jti): bool
    {
        return JwtRevocationModel::query()->where('jti', $jti)->exists();
    }

    public function recordReplacement(string $oldJti, string $newJti): void
    {
        JwtRevocationModel::query()->updateOrCreate(
            ['jti' => $oldJti],
            ['expires_at' => now()->addDay(), 'revoked_at' => now(), 'replaced_by' => $newJti],
        );
    }

    public function findReplacement(string $oldJti): ?string
    {
        $replacedBy = JwtRevocationModel::query()->where('jti', $oldJti)->value('replaced_by');

        return $replacedBy === null ? null : (string) $replacedBy;
    }
}
