<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\JwtAuthController;

Route::prefix('auth/jwt')->group(function (): void {
    Route::post('login', [JwtAuthController::class, 'login']);
    Route::post('refresh', [JwtAuthController::class, 'refresh']);
    Route::post('revoke', [JwtAuthController::class, 'revoke']);
});
