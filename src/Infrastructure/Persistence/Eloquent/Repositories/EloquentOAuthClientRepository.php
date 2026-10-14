<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Lab\Domain\Credential\OAuth\OAuthClient;
use Lab\Domain\Credential\OAuth\OAuthClientRepository;
use Lab\Infrastructure\Persistence\Eloquent\Models\OAuthClientModel;

/**
 * Implementación Eloquent del puerto de clientes OAuth.
 */
final class EloquentOAuthClientRepository implements OAuthClientRepository
{
    public function save(OAuthClient $client): OAuthClient
    {
        $model = OAuthClientModel::query()->where('client_id', $client->clientId())->first();

        if ($model === null) {
            $model = new OAuthClientModel();
            $model->setAttribute('client_id', $client->clientId());
        }

        $model->setAttribute('client_secret', $client->clientSecretHash());
        $model->setAttribute('name', $client->name());
        $model->save();
        $model->refresh();

        return OAuthClient::reconstitute(
            (int) $model->getAttribute('id'),
            (string) $model->getAttribute('client_id'),
            (string) $model->getAttribute('client_secret'),
            (string) $model->getAttribute('name'),
        );
    }

    public function findByClientId(string $clientId): ?OAuthClient
    {
        $model = OAuthClientModel::query()->select(['id', 'client_id', 'client_secret', 'name'])->where('client_id', $clientId)->first();

        if ($model === null) {
            return null;
        }

        return OAuthClient::reconstitute(
            (int) $model->getAttribute('id'),
            (string) $model->getAttribute('client_id'),
            (string) $model->getAttribute('client_secret'),
            (string) $model->getAttribute('name'),
        );
    }
}
