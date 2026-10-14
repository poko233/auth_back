<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Mapeo de excepciones de dominio a códigos HTTP.
        $notFound = [
            Lab\Domain\Identity\Exception\UserNotFound::class,
            Lab\Domain\Access\Exception\RoleNotFound::class,
            Lab\Domain\Access\Exception\PermissionNotFound::class,
            Lab\Domain\Ticketing\Exception\TicketNotFound::class,
            Lab\Domain\Credential\ApiKey\Exception\ApiKeyNotFound::class,
            Lab\Domain\Experiment\Exception\ExperimentNotFound::class,
        ];

        foreach ($notFound as $class) {
            $exceptions->render(fn (\Throwable $e, Request $request) => $e instanceof $class
                ? response()->json(['error' => 'not_found', 'detail' => $e->getMessage()], 404)
                : null);
        }

        $unprocessable = [
            InvalidArgumentException::class,
            Lab\Domain\Ticketing\Exception\InvalidStatusTransition::class,
        ];

        foreach ($unprocessable as $class) {
            $exceptions->render(fn (\Throwable $e, Request $request) => $e instanceof $class
                ? response()->json(['error' => 'unprocessable', 'detail' => $e->getMessage()], 422)
                : null);
        }

        $conflict = [
            Lab\Domain\Access\Exception\RoleInUse::class,
            Lab\Domain\Access\Exception\PermissionInUse::class,
            Lab\Domain\Access\Exception\SystemRoleProtected::class,
            Lab\Domain\Access\Exception\SystemPermissionProtected::class,
        ];

        foreach ($conflict as $class) {
            $exceptions->render(fn (\Throwable $e, Request $request) => $e instanceof $class
                ? response()->json(['error' => 'conflict', 'detail' => $e->getMessage()], 409)
                : null);
        }
    })->create();
