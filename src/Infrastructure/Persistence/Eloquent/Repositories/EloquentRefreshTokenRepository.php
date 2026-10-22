<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Lab\Domain\Credential\OAuth\RefreshToken;
use Lab\Domain\Credential\OAuth\RefreshTokenRepository;
use Lab\Infrastructure\Persistence\Eloquent\Models\OAuthRefreshTokenModel;

/**
 * Implementación Eloquent del puerto de refresh tokens.
 */
final class EloquentRefreshTokenRepository implements RefreshTokenRepository
{
    private const COLUMNS = ['id', 'refresh_token', 'access_token_id', 'expires_at', 'revoked_at', 'replaced_by', 'created_at', 'updated_at'];

    public function save(RefreshToken $token): RefreshToken
    {
        if ($token->id() !== null) {
            $model = OAuthRefreshTokenModel::query()->select(self::COLUMNS)->where('id', $token->id())->first();
        } else {
            $model = OAuthRefreshTokenModel::query()->select(self::COLUMNS)->where('refresh_token', $token->tokenHash())->first();
        }

        if ($model === null) {
            $model = new OAuthRefreshTokenModel();
            $model->setAttribute('refresh_token', $token->tokenHash());
            $model->setAttribute('access_token_id', $token->accessTokenId());
            $model->setAttribute('expires_at', $token->expiresAt());
        }

        $model->setAttribute('revoked_at', $token->revokedAt());
        $model->setAttribute('replaced_by', $token->replacedBy());
        $model->save();
        $model->refresh();

        return $this->toDomain($model);
    }

    public function findByHash(string $tokenHash): ?RefreshToken
    {
        $model = OAuthRefreshTokenModel::query()->select(self::COLUMNS)->where('refresh_token', $tokenHash)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function revokeByAccessTokenId(int $accessTokenId, DateTimeImmutable $revokedAt): void
    {
        OAuthRefreshTokenModel::query()
            ->where('access_token_id', $accessTokenId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $revokedAt]);
    }

    private function toDomain(OAuthRefreshTokenModel $model): RefreshToken
    {
        return RefreshToken::reconstitute(
            (int) $model->getAttribute('id'),
            (string) $model->getAttribute('refresh_token'),
            (int) $model->getAttribute('access_token_id'),
            $this->toImmutable($model->getAttribute('expires_at')),
            $this->toImmutable($model->getAttribute('revoked_at')),
            $model->getAttribute('replaced_by') !== null ? (string) $model->getAttribute('replaced_by') : null,
        );
    }

    private function toImmutable(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}
