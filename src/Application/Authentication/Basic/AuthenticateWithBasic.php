<?php

declare(strict_types=1);

namespace Lab\Application\Authentication\Basic;

use Lab\Application\Authentication\AuthenticatedPrincipal;
use Lab\Domain\Identity\Email;
use Lab\Domain\Identity\PasswordHasher;
use Lab\Domain\Identity\UserRepository;

/**
 * Autentica email+password contra users.
 *
 * Los tres fallos (inexistente, inactivo, password mala) son indistinguibles
 * hacia afuera (401 sin detalle). Con usuario inexistente se verifica un hash
 * dummy para no filtrar por timing.
 * Coste: exactamente 1 query (findByEmail), igual que los demás mecanismos.
 */
final class AuthenticateWithBasic
{
    private const DUMMY_HASH = '$2y$04$wEBU83VxbvnhxV3w39tgk.ndSQ2nj8ZkzjSMH7TD/05Xi/tEh0EO.';

    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
    ) {
    }

    public function execute(string $email, string $password): ?AuthenticatedPrincipal
    {
        try {
            $emailVo = Email::fromString($email);
        } catch (\InvalidArgumentException) {
            return null;
        }

        $user = $this->users->findByEmail($emailVo);

        if ($user === null) {
            $this->hasher->verify($password, self::DUMMY_HASH);

            return null;
        }

        if (! $user->isActive()) {
            return null;
        }

        if (! $this->hasher->verify($password, $user->passwordHash() ?? '')) {
            return null;
        }

        return new AuthenticatedPrincipal(
            $user->id(),
            'basic',
            ['email' => $emailVo->value()],
        );
    }
}
