<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\TicketController;
use Lab\Infrastructure\Http\Middleware\FlexibleAuthMiddleware;
use Lab\Infrastructure\Http\Middleware\MetricsRecorderMiddleware;

Route::prefix('tickets')->middleware([MetricsRecorderMiddleware::class, FlexibleAuthMiddleware::class])->group(function (): void {
    Route::get('/', [TicketController::class, 'index']);
    Route::post('/', [TicketController::class, 'store']);
    Route::get('{id}', [TicketController::class, 'show']);
    Route::put('{id}', [TicketController::class, 'update']);
    Route::delete('{id}', [TicketController::class, 'destroy']);
    Route::post('{id}/assign', [TicketController::class, 'assign']);
    Route::post('{id}/escalate', [TicketController::class, 'escalate']);
    Route::post('{id}/status', [TicketController::class, 'changeStatus']);
});
