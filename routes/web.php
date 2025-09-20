<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\HealthController;

Route::get('/', function () {
    return view('welcome');
});

// Metrics endpoint for Prometheus
Route::get('/metrics', [MetricsController::class, 'metrics']);

// Health check endpoints
Route::get('/health', [HealthController::class, 'check']);
Route::get('/health/ready', [HealthController::class, 'ready']);
Route::get('/health/live', [HealthController::class, 'live']);
