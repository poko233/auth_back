<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Lab\Domain\Credential\OAuth\AccessToken;
use Lab\Domain\Credential\OAuth\AccessTokenRepository;
use Lab\Infrastructure\Persistence\Eloquent\Models\OAuthAccessTokenModel;

/**
 * Implementación Eloquent del puerto de access tokens.
 */
final class EloquentAccessTokenRepository implements AccessTokenRepository
{
    private const COLUMNS = ['id', 'access_token', 'client_id', 'user_id', 'scopes', 'expires_at', 'revoked_at', 'created_at', 'updated_at'];

    public function save(AccessToken $token): AccessToken
    {
        if ($token->id() !== null) {
            $model = OAuthAccessTokenModel::query()->select(self::COLUMNS)->where('id', $token->id())->first();
        } else {
            $model = OAuthAccessTokenModel::query()->select(self::COLUMNS)->where('access_token', $token->tokenHash())->first();
        }

        if ($model === null) {
            $model = new OAuthAccessTokenModel();
            $model->setAttribute('access_token', $token->tokenHash());
            $model->setAttribute('client_id', $token->clientId());
            $model->setAttribute('user_id', $token->userId());
        }

        $model->setAttribute('scopes', json_encode($token->scopes()));
        $model->setAttribute('expires_at', $token->expiresAt());
        $model->setAttribute('revoked_at', $token->revokedAt());
        $model->save();
        $model->refresh();

        return $this->toDomain($model);
    }

    public function findById(int $id): ?AccessToken
    {
        $model = OAuthAccessTokenModel::query()->select(self::COLUMNS)->where('id', $id)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByHash(string $tokenHash): ?AccessToken
    {
        $model = OAuthAccessTokenModel::query()->select(self::COLUMNS)->where('access_token', $tokenHash)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function listByUser(int $userId): array
    {
        $models = OAuthAccessTokenModel::query()->select(self::COLUMNS)->where('user_id', $userId)->orderBy('id')->get();
        $tokens = [];

        foreach ($models as $model) {
            $tokens[] = $this->toDomain($model);
        }

        return $tokens;
    }

    private function toDomain(OAuthAccessTokenModel $model): AccessToken
    {
        return AccessToken::reconstitute(
            (int) $model->getAttribute('id'),
            (string) $model->getAttribute('access_token'),
            $model->getAttribute('client_id') !== null ? (int) $model->getAttribute('client_id') : null,
            (int) $model->getAttribute('user_id'),
            json_decode((string) $model->getAttribute('scopes'), true) ?? [],
            $this->toImmutable($model->getAttribute('expires_at')),
            $this->toImmutable($model->getAttribute('revoked_at')),
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
