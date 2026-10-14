<?php

declare(strict_types=1);

namespace Lab\Domain\Identity;

/**
 * Puerto de persistencia de usuarios (solo identidad).
 *
 * Los roles se consultan aparte en RoleRepository::currentRolesOf()
 * (Opción B): este puerto nunca devuelve roles.
 */
interface UserRepository
{
    /**
     * Persiste y devuelve el usuario con su id definitivo (la BD genera
     * autoincrementales: un User recién creado trae id temporal).
     */
    public function save(User $user): User;

    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    /**
     * @return list<User>
     */
    public function listAll(): array;

    public function delete(UserId $id): void;
}
