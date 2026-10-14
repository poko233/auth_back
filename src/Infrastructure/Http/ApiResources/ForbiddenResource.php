<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\ApiResources;

use Lab\Domain\Shared\AuthorizationResult;

/**
 * Respuesta 403 con la capa que rechazó (rbac | ticket_rule).
 *
 * Contrato del experimento: todo rechazo de autorización expone layer para
 * segmentar métricas. En Fase 6 lo usa el middleware de auth al denegar.
 */
final class ForbiddenResource
{
    /**
     * @return array{response: array<string, string>, status: int}
     */
    public static function fromResult(AuthorizationResult $result): array
    {
        return [
            'response' => [
                'error' => 'forbidden',
                'layer' => $result->failedLayer ?? 'rbac',
                'detail' => $result->reason ?? 'Acceso denegado.',
            ],
            'status' => 403,
        ];
    }
}
