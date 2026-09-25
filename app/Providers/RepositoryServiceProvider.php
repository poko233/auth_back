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
        // Fase 3+:
        // $this->app->bind(
        //     \Lab\Domain\Identity\UserRepository::class,
        //     \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository::class,
        // );
    }
}