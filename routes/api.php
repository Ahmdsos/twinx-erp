<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'version' => '1.0.0',
        ]);
    });
});

// Protected routes (require authentication + tenant context)
Route::prefix('v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // User profile
    Route::get('/me', function () {
        $user = auth()->user();
        $context = app(\App\Services\TenantContext::class);
        
        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'is_super_admin']),
            'tenant' => $context->toArray(),
        ]);
    });

    // TODO: Add more API routes here
});
