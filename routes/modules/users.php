<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\RoleController;
use Lab\Infrastructure\Http\Controllers\UserController;

Route::prefix('users')->group(function (): void {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('{id}', [UserController::class, 'show']);
    Route::put('{id}', [UserController::class, 'update']);
    Route::post('{id}/deactivate', [UserController::class, 'deactivate']);
    Route::post('{id}/password', [UserController::class, 'changePassword']);
    Route::post('{id}/roles', [RoleController::class, 'assign']);
    Route::delete('{id}/roles/{roleName}', [RoleController::class, 'revoke']);
});
