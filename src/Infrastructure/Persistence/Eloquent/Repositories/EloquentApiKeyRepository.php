<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use Lab\Domain\Credential\ApiKey\ApiKey;
use Lab\Domain\Credential\ApiKey\ApiKeyId;
use Lab\Domain\Credential\ApiKey\ApiKeyRepository;
use Lab\Infrastructure\Persistence\Eloquent\Mappers\ApiKeyMapper;
use Lab\Infrastructure\Persistence\Eloquent\Models\ApiKeyModel;

/**
 * Implementación Eloquent del puerto de API Keys.
 */
final class EloquentApiKeyRepository implements ApiKeyRepository
{
    private const COLUMNS = ['id', 'user_id', 'key_hash', 'label', 'expires_at', 'revoked_at', 'created_at', 'updated_at'];

    public function __construct(
        private ApiKeyMapper $mapper,
    ) {
    }

    public function save(ApiKey $apiKey): ApiKey
    {
        if ($apiKey->id() !== null) {
            $model = ApiKeyModel::query()->select(self::COLUMNS)->where('id', $apiKey->id()->value())->first();
        } else {
            $model = null;
        }

        if ($model === null) {
            $model = new ApiKeyModel();
            $model->setAttribute('user_id', $apiKey->userId());
            $model->setAttribute('key_hash', $apiKey->keyHash());
        }

        $model->setAttribute('label', $apiKey->label());
        $model->setAttribute('expires_at', $apiKey->expiresAt());
        $model->setAttribute('revoked_at', $apiKey->revokedAt());
        $model->save();
        $model->refresh();

        return $this->mapper->toDomain($model);
    }

    public function findById(ApiKeyId $id): ?ApiKey
    {
        $model = ApiKeyModel::query()->select(self::COLUMNS)->where('id', $id->value())->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findByHash(string $keyHash): ?ApiKey
    {
        $model = ApiKeyModel::query()->select(self::COLUMNS)->where('key_hash', $keyHash)->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function listByUser(int $userId): array
    {
        $models = ApiKeyModel::query()->select(self::COLUMNS)->where('user_id', $userId)->orderBy('id')->get();
        $keys = [];

        foreach ($models as $model) {
            $keys[] = $this->mapper->toDomain($model);
        }

        return $keys;
    }

    public function delete(ApiKeyId $id): void
    {
        ApiKeyModel::query()->where('id', $id->value())->delete();
    }
}
