<?php

use App\Http\Controllers\Api\AccountingIntegrationController;
use App\Http\Controllers\Api\AccountingPeriodController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChartOfAccountController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentSequenceController;
use App\Http\Controllers\Api\FinancialTransactionController;
use App\Http\Controllers\Api\GeneralLedgerController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\InventoryMovementController;
use App\Http\Controllers\Api\InventoryValuationController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\Reports\BalanceSheetController;
use App\Http\Controllers\Api\Reports\IncomeStatementController;
use App\Http\Controllers\Api\Reports\InventoryReportController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\StockCardController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TransactionCategoryController;
use App\Http\Controllers\Api\TrialBalanceController;
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

// Public Store & Application Settings
Route::get('/settings/public', [SettingsController::class, 'publicSettings']);

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
        Route::get('/preferences', [ProfileController::class, 'preferences']);
        Route::put('/preferences', [ProfileController::class, 'updatePreferences']);
        Route::get('/login-histories', [ProfileController::class, 'loginHistories']);
    });

    // Active Payment Methods (Bisa diakses kasir dan staff)
    Route::get('payment-methods', [PaymentMethodController::class, 'index']);

    // Master Data: Categories
    Route::apiResource('categories', CategoryController::class);
    Route::patch('categories/{category}/status', [CategoryController::class, 'updateStatus']);

    // Master Data: Units
    Route::apiResource('units', UnitController::class);
    Route::patch('units/{unit}/status', [UnitController::class, 'updateStatus']);

    // Master Data: Products
    Route::apiResource('products', ProductController::class);
    Route::patch('products/{product}/status', [ProductController::class, 'updateStatus']);

    // Master Data: Suppliers
    Route::get('suppliers/active', [SupplierController::class, 'active']);
    Route::apiResource('suppliers', SupplierController::class);

    // Modul Pembelian / Kulakan Barang (Purchases)
    Route::get('purchases', [PurchaseController::class, 'index']);
    Route::post('purchases', [PurchaseController::class, 'store']);
    Route::get('purchases/{id}', [PurchaseController::class, 'show'])->whereNumber('id');
    Route::post('purchases/{id}/receive', [PurchaseController::class, 'receive'])->whereNumber('id');
    Route::post('purchases/{id}/cancel', [PurchaseController::class, 'cancel'])->whereNumber('id');
    Route::post('purchases/{id}/pay-debt', [PurchaseController::class, 'payDebt'])->whereNumber('id');

    // Modul Penjualan POS Kasir
    Route::prefix('pos')->group(function () {
        Route::get('products', [PosController::class, 'products']);
        Route::get('products/barcode/{barcode}', [PosController::class, 'barcode']);
        Route::post('checkout', [PosController::class, 'checkout']);
        Route::get('sales', [PosController::class, 'sales']);
        Route::get('sales/{id}', [PosController::class, 'showSale'])->whereNumber('id');
        Route::get('sales/{id}/receipt', [PosController::class, 'receipt'])->whereNumber('id');
        Route::post('sales/{id}/cancel', [PosController::class, 'cancel'])->whereNumber('id');
    });

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

        // Kartu Stok (Stock Card per Product)
        Route::get('stock-card/{productId}', [StockCardController::class, 'show'])->whereNumber('productId');

        // Nilai Persediaan (Moving Weighted Average Inventory Valuation)
        Route::get('valuation', [InventoryValuationController::class, 'index']);
        Route::get('valuation/export/excel', [InventoryValuationController::class, 'exportExcel']);
        Route::get('valuation/export/pdf', [InventoryValuationController::class, 'exportPdf']);
    });

    // Accounting Module (Double-Entry Bookkeeping)
    // 1. Chart of Accounts
    Route::apiResource('chart-of-accounts', ChartOfAccountController::class);
    Route::patch('chart-of-accounts/{chart_of_account}/status', [ChartOfAccountController::class, 'updateStatus']);

    // 2. General Journal
    Route::get('journals', [JournalEntryController::class, 'index']);
    Route::post('journals', [JournalEntryController::class, 'store']);
    Route::get('journals/{journal}', [JournalEntryController::class, 'show'])->whereNumber('journal');
    Route::put('journals/{journal}', [JournalEntryController::class, 'update'])->whereNumber('journal');
    Route::delete('journals/{journal}', [JournalEntryController::class, 'destroy'])->whereNumber('journal');
    Route::post('journals/{journal}/post', [JournalEntryController::class, 'post'])->whereNumber('journal');
    Route::post('journals/{journal}/reverse', [JournalEntryController::class, 'reverse'])->whereNumber('journal');

    // 3. General Ledger
    Route::get('general-ledger', [GeneralLedgerController::class, 'index']);
    Route::get('general-ledger/{accountId}', [GeneralLedgerController::class, 'show'])->whereNumber('accountId');

    // 4. Trial Balance
    Route::get('trial-balance', [TrialBalanceController::class, 'index']);

    // 5. Accounting Integrations & Mappings
    Route::prefix('accounting')->group(function () {
        Route::post('sync-transaction/{transaction}', [AccountingIntegrationController::class, 'syncTransaction']);
        Route::post('sync-inventory/{movement}', [AccountingIntegrationController::class, 'syncInventory']);
        Route::get('mappings', [AccountingIntegrationController::class, 'mappings']);
    });

    // 6. Dashboard Endpoints
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('financial-summary', [DashboardController::class, 'financialSummary']);
        Route::get('financial-chart', [DashboardController::class, 'financialChart']);
        Route::get('expense-distribution', [DashboardController::class, 'expenseDistribution']);
        Route::get('recent-activities', [DashboardController::class, 'recentActivities']);
        Route::get('inventory-summary', [DashboardController::class, 'inventorySummary']);
        Route::get('low-stock', [DashboardController::class, 'lowStock']);
    });

    // 7. Reports Endpoints (Income Statement, Balance Sheet, Inventory & Movements)
    Route::prefix('reports')->group(function () {
        // Income Statement
        Route::get('income-statement', [IncomeStatementController::class, 'index']);
        Route::get('income-statement/export/pdf', [IncomeStatementController::class, 'exportPdf']);
        Route::get('income-statement/export/excel', [IncomeStatementController::class, 'exportExcel']);

        // Balance Sheet
        Route::get('balance-sheet', [BalanceSheetController::class, 'index']);
        Route::get('balance-sheet/export/pdf', [BalanceSheetController::class, 'exportPdf']);
        Route::get('balance-sheet/export/excel', [BalanceSheetController::class, 'exportExcel']);

        // Inventory Report
        Route::get('inventory', [InventoryReportController::class, 'index']);
        Route::get('inventory/export/pdf', [InventoryReportController::class, 'exportPdf']);
        Route::get('inventory/export/excel', [InventoryReportController::class, 'exportExcel']);

        // Stock Movements Report
        Route::get('stock-movements', [InventoryReportController::class, 'stockMovements']);
        Route::get('stock-movements/export/pdf', [InventoryReportController::class, 'stockMovementsExportPdf']);
        Route::get('stock-movements/export/excel', [InventoryReportController::class, 'stockMovementsExportExcel']);

        // Inventory Valuation Report (Nilai Persediaan Moving Weighted Average)
        Route::get('inventory-valuation', [InventoryValuationController::class, 'index']);
        Route::get('inventory-valuation/export/pdf', [InventoryValuationController::class, 'exportPdf']);
        Route::get('inventory-valuation/export/excel', [InventoryValuationController::class, 'exportExcel']);
    });
});

// Admin-Only Routes (Role-based access protection)
Route::middleware(['auth:sanctum', 'active', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/check', [AdminController::class, 'check']);

    // Admin User Management API
    Route::apiResource('users', AdminUserController::class);
    Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole']);
    Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus']);

    // Centralized Settings Management API
    Route::get('settings', [SettingsController::class, 'index']);
    Route::put('settings', [SettingsController::class, 'update']);

    // Roles & Granular Permissions API
    Route::get('roles', [RolePermissionController::class, 'index']);
    Route::put('roles/{roleId}/permissions', [RolePermissionController::class, 'update'])->whereNumber('roleId');

    // Document Sequences API
    Route::get('document-sequences', [DocumentSequenceController::class, 'index']);
    Route::put('document-sequences/{id}', [DocumentSequenceController::class, 'update'])->whereNumber('id');

    // Accounting Periods & Fiscal Year Locking API
    Route::get('accounting-periods', [AccountingPeriodController::class, 'index']);
    Route::patch('accounting-periods/{id}/lock', [AccountingPeriodController::class, 'toggleLock'])->whereNumber('id');
    Route::post('accounting-periods/{id}/close', [AccountingPeriodController::class, 'close'])->whereNumber('id');

    // Payment Methods Management API
    Route::get('payment-methods', [PaymentMethodController::class, 'index']);
    Route::post('payment-methods', [PaymentMethodController::class, 'store']);
    Route::put('payment-methods/{id}', [PaymentMethodController::class, 'update'])->whereNumber('id');
    Route::patch('payment-methods/{id}/status', [PaymentMethodController::class, 'toggleStatus'])->whereNumber('id');

    // Database Backup & Restore API
    Route::get('backups', [BackupController::class, 'index']);
    Route::post('backups', [BackupController::class, 'create']);
    Route::get('backups/{id}/download', [BackupController::class, 'download'])->whereNumber('id');
    Route::post('backups/{id}/restore', [BackupController::class, 'restore'])->whereNumber('id');

    // Audit Trail / Activity Logs API
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
});
