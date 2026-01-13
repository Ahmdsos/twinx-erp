<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\PosController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\UnitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| TWINX ERP - RESTful API v1
|
*/

// =====================================================
// Public Routes (No Authentication)
// =====================================================

Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'version' => '1.0.0',
        ]);
    });

    // Authentication
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// =====================================================
// Protected Routes (Require Authentication)
// =====================================================

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Auth
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // User profile
    Route::get('/me', function () {
        $user = auth()->user();
        $context = app(\App\Services\TenantContext::class);
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'is_super_admin']),
                'tenant' => $context->toArray(),
            ],
        ]);
    });
});

// =====================================================
// Protected Routes with Tenant Context
// =====================================================

Route::prefix('v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    
    // Products
    Route::apiResource('products', ProductController::class);
    Route::post('products/import', [ProductController::class, 'import']);
    Route::get('products/export', [ProductController::class, 'export']);
    Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete']);
    Route::put('products/bulk-update', [ProductController::class, 'bulkUpdate']);
    Route::post('products/{product}/image', [ProductController::class, 'uploadImage']);
    
    // Categories
    Route::get('categories/tree', [CategoryController::class, 'tree']);
    Route::post('categories/reorder', [CategoryController::class, 'reorder']);
    Route::apiResource('categories', CategoryController::class);
    
    // Brands
    Route::get('brands/all', [BrandController::class, 'all']);
    Route::apiResource('brands', BrandController::class);
    
    // Units
    Route::get('units/all', [UnitController::class, 'all']);
    Route::post('units/convert', [UnitController::class, 'convert']);
    Route::apiResource('units', UnitController::class);
    
    // Customers
    Route::get('customers/all', [CustomerController::class, 'all']);
    Route::post('customers/bulk-delete', [CustomerController::class, 'bulkDelete']);
    Route::get('customers/{customer}/statement', [CustomerController::class, 'statement']);
    Route::apiResource('customers', CustomerController::class);
    
    // Suppliers
    Route::get('suppliers/all', [SupplierController::class, 'all']);
    Route::post('suppliers/bulk-delete', [SupplierController::class, 'bulkDelete']);
    Route::get('suppliers/{supplier}/statement', [SupplierController::class, 'statement']);
    Route::apiResource('suppliers', SupplierController::class);
    
    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::put('/', [SettingsController::class, 'update']);
        Route::post('/logo', [SettingsController::class, 'uploadLogo']);
        Route::get('/currencies', [SettingsController::class, 'currencies']);
        Route::put('/currencies', [SettingsController::class, 'updateCurrencies']);
        Route::post('/reset', [SettingsController::class, 'resetToDefault']);
    });
    
    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/sales-kpis', [DashboardController::class, 'salesKpis']);
        Route::get('/inventory-kpis', [DashboardController::class, 'inventoryKpis']);
        Route::get('/finance-kpis', [DashboardController::class, 'financeKpis']);
        Route::get('/top-products', [DashboardController::class, 'topProducts']);
        Route::get('/top-customers', [DashboardController::class, 'topCustomers']);
        Route::get('/sales-chart', [DashboardController::class, 'salesChart']);
    });
    
    // POS
    Route::prefix('pos')->group(function () {
        Route::get('/products', [PosController::class, 'products']);
        Route::get('/customers/search', [PosController::class, 'searchCustomers']);
        Route::post('/sale', [PosController::class, 'quickSale']);
    });
});

