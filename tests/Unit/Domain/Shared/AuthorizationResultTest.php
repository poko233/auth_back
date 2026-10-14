<?php

declare(strict_types=1);

use Lab\Domain\Shared\AuthorizationResult;

/**
 * Tests del VO de resultado de autorización en dos capas.
 */
it('allowed no tiene capa de fallo', function (): void {
    $result = AuthorizationResult::allowed();

    expect($result->allowed)->toBeTrue()
        ->and($result->failedLayer)->toBeNull();
});

it('distingue rechazo por RBAC de rechazo por regla de ticket', function (): void {
    $rbac = AuthorizationResult::deniedByRbac('Sin permiso DELETE.');
    $rule = AuthorizationResult::deniedByTicketRule('Ticket CLOSED.');

    expect($rbac->allowed)->toBeFalse()
        ->and($rbac->failedLayer)->toBe('rbac')
        ->and($rule->allowed)->toBeFalse()
        ->and($rule->failedLayer)->toBe('ticket_rule');
});
