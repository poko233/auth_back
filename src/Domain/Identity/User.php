<?php

declare(strict_types=1);

namespace Lab\Domain\Identity;

use InvalidArgumentException;

/**
 * Entidad User: identidad pura, sin roles embebidos.
 *
 * El rol es la variable independiente del experimento y se consulta aparte
 * vía RoleRepository::currentRolesOf(). Así es imposible autorizar con un
 * rol viejo cacheado dentro del User: no hay dónde guardarlo.
 *
 * Construcción (opción A): constructor privado + factory create() que fuerza
 * isActive=true. Un usuario inactivo se crea activo y se desactiva después.
 *
 * Contraseña (opción 1): el User transporta el hash ya calculado
 * (?string $passwordHash). El hash lo produce el caso de uso vía el puerto
 * PasswordHasher; el Domain no sabe hashear ni valida el algoritmo.
 * Nullable porque un User en memoria puede no tener password aún;
 * una vez persistido siempre la tiene.
 */
final class User
{
    private function __construct(
        private UserId $id,
        private Email $email,
        private string $name,
        private ?string $passwordHash,
        private bool $isActive,
    ) {
    }

    public static function create(UserId $id, Email $email, string $name, ?string $passwordHash = null): self
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new InvalidArgumentException('El nombre del usuario no puede estar vacío.');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('El nombre del usuario no puede superar 255 caracteres.');
        }

        return new self($id, $email, $trimmed, $passwordHash, true);
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function passwordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function rename(string $name): void
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new InvalidArgumentException('El nombre del usuario no puede estar vacío.');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('El nombre del usuario no puede superar 255 caracteres.');
        }

        $this->name = $trimmed;
    }

    public function changeEmail(Email $email): void
    {
        $this->email = $email;
    }

    public function changePasswordHash(string $hash): void
    {
        if (trim($hash) === '') {
            throw new InvalidArgumentException('El hash de contraseña no puede estar vacío.');
        }

        $this->passwordHash = $hash;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }
}
