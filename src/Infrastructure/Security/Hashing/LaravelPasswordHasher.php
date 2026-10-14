<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Security\Hashing;

use Illuminate\Support\Facades\Hash;
use Lab\Domain\Identity\PasswordHasher;

/**
 * Hasher de contraseñas con el driver de Laravel.
 */
final class LaravelPasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return Hash::make($plain);
    }

    public function verify(string $plain, string $hash): bool
    {
        return Hash::check($plain, $hash);
    }
}
