<?php

use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FinToko REST API Routes (API Version 1: /api/v1)
|--------------------------------------------------------------------------
|
| Konfigurasi route REST API FinToko.
| Prefix /api/v1 telah ditentukan pada bootstrap/app.php.
|
*/

// Public Health Check Endpoint
Route::get('/health', [HealthController::class, 'index']);

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Rate-limited login (10 attempts per minute)
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // Authenticated Auth Routes
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

// Authenticated User Profile Routes (accessible by both Admin & Staff)
Route::middleware(['auth:sanctum', 'active'])->prefix('user')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/change-password', [ProfileController::class, 'changePassword']);
});

// Admin-Only Routes (Role-based access protection)
Route::middleware(['auth:sanctum', 'active', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/check', [AdminController::class, 'check']);

    // Admin User Management API
    Route::apiResource('users', AdminUserController::class);
    Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole']);
    Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus']);
});
