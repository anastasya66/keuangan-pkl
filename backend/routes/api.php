<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FinToko API Routes (Prefix: /api/v1)
|--------------------------------------------------------------------------
|
| Semua endpoint REST API FinToko didaftarkan di sini.
| Prefix /api/v1 dikonfigurasi melalui bootstrap/app.php.
|
*/

// Public Health Check Endpoint
Route::get('/health', [HealthController::class, 'index']);

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    // Authenticated Auth Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Admin-Only Routes (Role-based access demonstration)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/check', [AdminController::class, 'check']);
});
