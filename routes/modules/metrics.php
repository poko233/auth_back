<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Lab\Infrastructure\Http\Controllers\MetricController;

Route::prefix('experiments/{experimentId}/metrics')->group(function (): void {
    Route::get('/', [MetricController::class, 'index']);
    Route::get('compare', [MetricController::class, 'compare']);
});
