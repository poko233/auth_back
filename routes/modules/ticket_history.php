<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\TicketHistoryController;
use Lab\Infrastructure\Http\Middleware\FlexibleAuthMiddleware;
use Lab\Infrastructure\Http\Middleware\MetricsRecorderMiddleware;

Route::prefix('tickets/{id}')->middleware([MetricsRecorderMiddleware::class, FlexibleAuthMiddleware::class])->group(function (): void {
    Route::get('history', [TicketHistoryController::class, 'history']);
    Route::get('views', [TicketHistoryController::class, 'views']);
    Route::get('escalations', [TicketHistoryController::class, 'escalations']);
});
