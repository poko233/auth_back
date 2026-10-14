<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\ApiKeyAuthController;

Route::prefix('auth/api-key')->group(function (): void {
    Route::post('login', [ApiKeyAuthController::class, 'login']);
    Route::post('issue', [ApiKeyAuthController::class, 'issue']);
    Route::post('revoke/{id}', [ApiKeyAuthController::class, 'revoke']);
    Route::get('users/{userId}/keys', [ApiKeyAuthController::class, 'index']);
});
