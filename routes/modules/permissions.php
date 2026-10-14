<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\PermissionController;

Route::prefix('permissions')->group(function (): void {
    Route::get('/', [PermissionController::class, 'index']);
    Route::post('/', [PermissionController::class, 'store']);
    Route::put('{name}', [PermissionController::class, 'update']);
    Route::delete('{name}', [PermissionController::class, 'destroy']);
});
