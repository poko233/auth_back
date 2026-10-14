<?php

declare(strict_types=1);

use Lab\Domain\Identity\Email;
use Lab\Domain\Identity\User;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Identity\UserRepository;

/**
 * Tests de dominio de Identity (sin Laravel, sin BD).
 *
 * Fake en memoria del puerto para verificar el contrato del repositorio.
 * Clona al guardar y al devolver: en PHP los objetos son referencias y el
 * repo real (Eloquent) devuelve una instancia nueva en cada findById().
 * Sin clone, el fake mentiría ante save()+mutación sobre la misma instancia.
 */
final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    public function save(User $user): User
    {
        $this->users[$user->id()->value()] = clone $user;

        return clone $user;
    }

    public function findById(UserId $id): ?User
    {
        $found = $this->users[$id->value()] ?? null;

        return $found === null ? null : clone $found;
    }

    public function findByEmail(Email $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email()->equals($email)) {
                return clone $user;
            }
        }

        return null;
    }

    public function delete(UserId $id): void
    {
        unset($this->users[$id->value()]);
    }

    public function listAll(): array
    {
        return array_values(array_map(fn (User $user): User => clone $user, $this->users));
    }
}

it('crea un usuario activo con email válido', function (): void {
    $user = User::create(UserId::fromString('1'), Email::fromString('Ana@lab.test'), 'Ana');

    expect($user->id()->value())->toBe('1')
        ->and($user->email()->value())->toBe('ana@lab.test')
        ->and($user->name())->toBe('Ana')
        ->and($user->isActive())->toBeTrue()
        ->and($user->passwordHash())->toBeNull();
});

it('Email rechaza vacíos, largos e inválidos', function (): void {
    expect(fn () => Email::fromString('   '))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Email::fromString('no-es-email'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Email::fromString(str_repeat('a', 250) . '@x.test'))->toThrow(InvalidArgumentException::class);
});

it('Email normaliza a minúsculas y compara por valor', function (): void {
    expect(Email::fromString('Ana@Lab.Test')->equals(Email::fromString('ana@lab.test')))->toBeTrue();
});

it('rechaza nombres vacíos', function (): void {
    expect(fn () => User::create(UserId::fromString('2'), Email::fromString('a@lab.test'), '  '))
        ->toThrow(InvalidArgumentException::class);
});

it('transporta el hash y lo cambia con mutación explícita', function (): void {
    $user = User::create(UserId::fromString('3'), Email::fromString('b@lab.test'), 'Beto', 'hash-1');

    expect($user->passwordHash())->toBe('hash-1');

    $user->changePasswordHash('hash-2');

    expect($user->passwordHash())->toBe('hash-2')
        ->and(fn () => $user->changePasswordHash('  '))->toThrow(InvalidArgumentException::class);
});

it('activa y desactiva sin tocar la identidad', function (): void {
    $user = User::create(UserId::fromString('4'), Email::fromString('c@lab.test'), 'Ceci');
    $user->deactivate();

    expect($user->isActive())->toBeFalse();

    $user->activate();

    expect($user->isActive())->toBeTrue()
        ->and($user->id()->value())->toBe('4');
});

it('el puerto guarda, busca por id y por email, y elimina', function (): void {
    $repo = new InMemoryUserRepository();
    $user = User::create(UserId::fromString('5'), Email::fromString('d@lab.test'), 'Dani');
    $repo->save($user);

    expect($repo->findById(UserId::fromString('5')))->not->toBeNull()
        ->and($repo->findByEmail(Email::fromString('d@lab.test')))->not->toBeNull();

    $repo->delete(UserId::fromString('5'));

    expect($repo->findById(UserId::fromString('5')))->toBeNull();
});

it('el fake no aliaza: mutar tras save no contamina lo guardado', function (): void {
    $repo = new InMemoryUserRepository();
    $user = User::create(UserId::fromString('6'), Email::fromString('e@lab.test'), 'Eli');
    $repo->save($user);
    $user->rename('Mutado');

    expect($repo->findById(UserId::fromString('6'))->name())->toBe('Eli');
});
