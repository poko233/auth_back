<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\ApiKeyAuthController;
use Lab\Infrastructure\Http\Middleware\FlexibleAuthMiddleware;

Route::prefix('auth/api-key')->group(function (): void {
    Route::post('login', [ApiKeyAuthController::class, 'login']);
    Route::middleware(FlexibleAuthMiddleware::class)->group(function (): void {
        Route::post('issue', [ApiKeyAuthController::class, 'issue']);
        Route::post('revoke/{id}', [ApiKeyAuthController::class, 'revoke'])->whereNumber('id');
        Route::get('users/{userId}/keys', [ApiKeyAuthController::class, 'index'])->whereNumber('userId');
    });
});
