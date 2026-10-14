<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Composition root de persistencia.
 *
 * Aquí —y solo aquí— se conectan los puertos del Domain con sus
 * implementaciones concretas en Lab\Infrastructure\Persistence.
 *
 * Vacío en Fase 2: se poblará por módulo a partir de Fase 3.
 */
final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Lab\Domain\Identity\UserRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Access\RoleRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoleRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Access\PermissionRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentPermissionRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Ticketing\TicketRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentTicketRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Ticketing\TicketViewRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentTicketViewRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Ticketing\TicketEscalationRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentTicketEscalationRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Ticketing\TicketHistoryRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentTicketHistoryRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Shared\TransactionManager::class,
            \Lab\Infrastructure\Persistence\Transactions\LaravelTransactionManager::class,
        );

        $this->app->bind(
            \Lab\Domain\Credential\ApiKey\ApiKeyRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentApiKeyRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Credential\Jwt\JwtRevocationRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentJwtRevocationRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Credential\OAuth\OAuthClientRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentOAuthClientRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Credential\OAuth\AccessTokenRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccessTokenRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Credential\OAuth\RefreshTokenRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentRefreshTokenRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Experiment\ExperimentRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentExperimentRepository::class,
        );

        $this->app->bind(
            \Lab\Domain\Experiment\MetricRepository::class,
            \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentMetricRepository::class,
        );
    }
}