<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lab\Application\Access\AuthorizeAction;
use Lab\Domain\Access\PermissionName;
use Lab\Infrastructure\Http\ApiResources\ForbiddenResource;
use Symfony\Component\HttpFoundation\Response;

/** Reutiliza el mismo permiso administrativo que la gestión de API Keys. */
final class ExperimentAdminMiddleware
{
    public function __construct(private AuthorizeAction $authorize)
    {
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $result = $this->authorize->execute(ApiKeyAuthMiddleware::principal($request), PermissionName::fromString('ADMIN'));

        if (! $result->allowed) {
            $payload = ForbiddenResource::fromResult($result);

            return response()->json($payload['response'], $payload['status']);
        }

        return $next($request);
    }
}
