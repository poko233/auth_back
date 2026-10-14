<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\BasicAuthController;

Route::prefix('auth/basic')->group(function (): void {
    Route::post('login', [BasicAuthController::class, 'login']);
});
