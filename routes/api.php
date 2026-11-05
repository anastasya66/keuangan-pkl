<?php

use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FinancialTransactionController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\InventoryMovementController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TransactionCategoryController;
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

    // Financial Management: Transaction Categories
    Route::apiResource('transaction-categories', TransactionCategoryController::class);
    Route::patch('transaction-categories/{transaction_category}/status', [TransactionCategoryController::class, 'updateStatus']);

    // Financial Management: Transactions
    Route::get('transactions', [FinancialTransactionController::class, 'index']);
    Route::post('transactions', [FinancialTransactionController::class, 'store']);
    Route::get('transactions/{transaction}', [FinancialTransactionController::class, 'show']);
    Route::put('transactions/{transaction}', [FinancialTransactionController::class, 'update']);
    Route::patch('transactions/{transaction}/cancel', [FinancialTransactionController::class, 'cancel']);
    Route::delete('transactions/{transaction}', [FinancialTransactionController::class, 'destroy']);

    // Dedicated Income & Expense aliases
    Route::get('incomes', [FinancialTransactionController::class, 'index'])->name('incomes.index');
    Route::post('incomes', [FinancialTransactionController::class, 'store'])->name('incomes.store');
    Route::get('expenses', [FinancialTransactionController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [FinancialTransactionController::class, 'store'])->name('expenses.store');

    // Inventory Management
    Route::prefix('inventory')->group(function () {
        // Stock In
        Route::get('stock-in', [InventoryMovementController::class, 'stockInList']);
        Route::post('stock-in', [InventoryController::class, 'stockIn']);

        // Stock Out
        Route::get('stock-out', [InventoryMovementController::class, 'stockOutList']);
        Route::post('stock-out', [InventoryController::class, 'stockOut']);

        // Stock Adjustments
        Route::get('adjustments', [InventoryMovementController::class, 'adjustmentList']);
        Route::post('adjustments', [InventoryController::class, 'adjust']);

        // Stock Movements History
        Route::get('movements', [InventoryMovementController::class, 'index']);
        Route::get('movements/{movement}', [InventoryMovementController::class, 'show']);

        // Inventory Stock Overview & Product Stock Detail
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('{product}', [InventoryController::class, 'show'])->whereNumber('product');
    });
});

// Admin-Only Routes (Role-based access protection)
Route::middleware(['auth:sanctum', 'active', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/check', [AdminController::class, 'check']);

    // Admin User Management API
    Route::apiResource('users', AdminUserController::class);
    Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole']);
    Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus']);
});
