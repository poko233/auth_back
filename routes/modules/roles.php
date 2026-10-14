<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\RoleController;

Route::prefix('roles')->group(function (): void {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
    Route::put('{name}', [RoleController::class, 'update']);
    Route::delete('{name}', [RoleController::class, 'destroy']);
    Route::put('{name}/permissions', [RoleController::class, 'syncPermissions']);
});
