<?php

declare(strict_types=1);

use Lab\Domain\Shared\AuthorizationResult;
use Lab\Infrastructure\Http\ApiResources\ForbiddenResource;

/**
 * Tests del contrato 403+layer (lo consumirá el middleware en Fase 6).
 */
it('expone layer rbac en el JSON de 403', function (): void {
    $payload = ForbiddenResource::fromResult(AuthorizationResult::deniedByRbac('Sin permiso DELETE.'));

    expect($payload['status'])->toBe(403)
        ->and($payload['response'])->toBe([
            'error' => 'forbidden',
            'layer' => 'rbac',
            'detail' => 'Sin permiso DELETE.',
        ]);
});

it('expone layer ticket_rule en el JSON de 403', function (): void {
    $payload = ForbiddenResource::fromResult(AuthorizationResult::deniedByTicketRule('Ticket CLOSED.'));

    expect($payload['status'])->toBe(403)
        ->and($payload['response']['layer'])->toBe('ticket_rule');
});
