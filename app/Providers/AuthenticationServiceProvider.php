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
        $this->app->bind(
            \Lab\Domain\Identity\PasswordHasher::class,
            \Lab\Infrastructure\Security\Hashing\LaravelPasswordHasher::class,
        );

        $this->app->bind(
            \Lab\Domain\Credential\ApiKey\ApiKeyGenerator::class,
            \Lab\Infrastructure\Security\ApiKey\SecureApiKeyGenerator::class,
        );

        $this->app->bind(
            \Lab\Domain\Credential\Jwt\JwtSigner::class,
            fn (): \Lab\Infrastructure\Security\Jwt\FirebaseJwtSigner => new \Lab\Infrastructure\Security\Jwt\FirebaseJwtSigner(
                (string) config('jwt.secret'),
                (string) config('jwt.algo', 'HS256'),
            ),
        );
    }
}