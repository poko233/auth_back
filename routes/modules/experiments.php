<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\ExperimentController;
use Lab\Infrastructure\Http\Middleware\ExperimentAdminMiddleware;
use Lab\Infrastructure\Http\Middleware\FlexibleAuthMiddleware;

Route::prefix('experiments')->middleware([FlexibleAuthMiddleware::class, ExperimentAdminMiddleware::class])->group(function (): void {
    Route::get('/', [ExperimentController::class, 'index']);
    Route::post('/', [ExperimentController::class, 'store']);
    Route::post('{id}/start', [ExperimentController::class, 'start'])->whereNumber('id');
    Route::post('{id}/finish', [ExperimentController::class, 'finish'])->whereNumber('id');
    Route::post('{id}/privileges', [ExperimentController::class, 'privileges']);
    Route::get('{id}/events', [ExperimentController::class, 'events']);
});
