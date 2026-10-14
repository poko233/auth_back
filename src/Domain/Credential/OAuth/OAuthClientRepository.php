<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\OAuth;

/**
 * Puerto de clientes OAuth.
 */
interface OAuthClientRepository
{
    public function save(OAuthClient $client): OAuthClient;

    public function findByClientId(string $clientId): ?OAuthClient;
}
