<?php

use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UnitController;
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

// Authenticated Routes (Accessible by both Admin & Staff according to Policies)
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // User Profile Routes
    Route::prefix('user')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::put('/change-password', [ProfileController::class, 'changePassword']);
    });

    // Master Data: Categories
    Route::apiResource('categories', CategoryController::class);
    Route::patch('categories/{category}/status', [CategoryController::class, 'updateStatus']);

    // Master Data: Units
    Route::apiResource('units', UnitController::class);
    Route::patch('units/{unit}/status', [UnitController::class, 'updateStatus']);

    // Master Data: Products
    Route::apiResource('products', ProductController::class);
    Route::patch('products/{product}/status', [ProductController::class, 'updateStatus']);
});

// Admin-Only Routes (Role-based access protection)
Route::middleware(['auth:sanctum', 'active', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/check', [AdminController::class, 'check']);

    // Admin User Management API
    Route::apiResource('users', AdminUserController::class);
    Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole']);
    Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus']);
});
