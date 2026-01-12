<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\ProductController;
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
    
    // Customers
    Route::apiResource('customers', CustomerController::class);
    
    // TODO: Add more resources
    // Route::apiResource('invoices', InvoiceController::class);
    // Route::apiResource('suppliers', SupplierController::class);
    // Route::apiResource('employees', EmployeeController::class);
});

