<?php

declare(strict_types=1);

namespace Lab\Domain\Identity;

/**
 * Puerto de hashing de contraseñas.
 *
 * El Domain define la interfaz; el caso de uso la invoca para producir el
 * hash que viaja en User::$passwordHash.
 */
interface PasswordHasher
{
    public function hash(string $plain): string;

    public function verify(string $plain, string $hash): bool;
}
