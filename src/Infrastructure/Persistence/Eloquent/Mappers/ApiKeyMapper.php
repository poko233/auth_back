<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Mappers;

use DateTimeImmutable;
use DateTimeInterface;
use Lab\Domain\Credential\ApiKey\ApiKey;
use Lab\Domain\Credential\ApiKey\ApiKeyId;
use Lab\Infrastructure\Persistence\Eloquent\Models\ApiKeyModel;

/**
 * Traductor entre ApiKeyModel (Eloquent) y ApiKey (Domain).
 */
final class ApiKeyMapper
{
    public function toDomain(ApiKeyModel $model): ApiKey
    {
        return ApiKey::reconstitute(
            ApiKeyId::fromInt((int) $model->getAttribute('id')),
            (int) $model->getAttribute('user_id'),
            (string) $model->getAttribute('key_hash'),
            $model->getAttribute('label') !== null ? (string) $model->getAttribute('label') : null,
            $this->toImmutable($model->getAttribute('expires_at')),
            $this->toImmutable($model->getAttribute('revoked_at')),
            $this->toImmutable($model->getAttribute('created_at')),
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
