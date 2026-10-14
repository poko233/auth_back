<?php

declare(strict_types=1);

namespace Lab\Domain\Shared;

/**
 * Resultado de una autorización en dos capas.
 *
 * - allowed=true: pasa.
 * - allowed=false + failedLayer='rbac': el rol no tiene el permiso.
 * - allowed=false + failedLayer='ticket_rule': el RBAC pasa pero una regla
 *   del ticket (estado, borrado) rechaza. Así el experimento segmenta los 403
 *   sin ambigüedad.
 */
final readonly class AuthorizationResult
{
    public function __construct(
        public bool $allowed,
        public ?string $failedLayer = null,
        public ?string $reason = null,
    ) {
    }

    public static function allowed(): self
    {
        return new self(true);
    }

    public static function deniedByRbac(string $reason): self
    {
        return new self(false, 'rbac', $reason);
    }

    public static function deniedByTicketRule(string $reason): self
    {
        return new self(false, 'ticket_rule', $reason);
    }
}
