<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\ExperimentController;

Route::prefix('experiments')->group(function (): void {
    Route::get('/', [ExperimentController::class, 'index']);
    Route::post('/', [ExperimentController::class, 'start']);
    Route::post('{id}/finish', [ExperimentController::class, 'finish']);
    Route::post('{id}/privileges', [ExperimentController::class, 'privileges']);
    Route::get('{id}/events', [ExperimentController::class, 'events']);
});
