<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\JwtAuthController;
use Lab\Infrastructure\Http\Middleware\RecordCredentialRevocation;

Route::prefix('auth/jwt')->group(function (): void {
    Route::post('login', [JwtAuthController::class, 'login']);
    Route::post('refresh', [JwtAuthController::class, 'refresh']);
    Route::post('revoke', [JwtAuthController::class, 'revoke'])
        ->middleware(RecordCredentialRevocation::class.':jwt');
});
