<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\OAuthController;
use Lab\Infrastructure\Http\Middleware\RecordCredentialRevocation;

Route::prefix('auth/oauth')->group(function (): void {
    Route::post('token', [OAuthController::class, 'token']);
    Route::post('refresh', [OAuthController::class, 'refresh']);
    Route::post('introspect', [OAuthController::class, 'introspect']);
    Route::post('revoke', [OAuthController::class, 'revoke'])
        ->middleware(RecordCredentialRevocation::class.':oauth');
});
