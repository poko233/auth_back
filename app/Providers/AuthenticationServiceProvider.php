<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Composition root de autenticación.
 *
 * Conecta los puertos del Domain relacionados con credenciales con las
 * implementaciones concretas de Infrastructure (JWT, hashing, generación
 * aleatoria), y registrará los cuatro mecanismos de autenticación como
 * casos de uso resolubles por el contenedor.
 *
 * Vacío en Fase 2: se poblará en Fases 5–8.
 */
final class AuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Fase 5+:
        // $this->app->bind(JwtSigner::class, FirebaseJwtSigner::class);
        // $this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);
    }
}