<?php

// ============================================================================
// FILE 10: API Routes
// Path: routes/api.php
// ============================================================================

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\V1\Admin\AdminManagementController;
use App\Http\Controllers\Api\V1\User\PackageController as UserPackageController;
use App\Http\Controllers\Api\V1\Admin\PackageController as AdminPackageController;


Route::prefix('v1')->group(function () {
    
    // Public authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAllDevices']);
        });
    });
});

// Admin Routes
Route::prefix('v1/admin')->group(function () {
    
    // Public admin routes
    Route::post('auth/login', [AdminAuthController::class, 'login']);

    // Protected admin routes
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('me', [AdminAuthController::class, 'me']);
            Route::post('logout', [AdminAuthController::class, 'logout']);
            Route::post('logout-all', [AdminAuthController::class, 'logoutAllDevices']);
            Route::post('change-password', [AdminAuthController::class, 'changePassword']);
        });
    });

    // Super admin only routes
    Route::middleware(['auth:sanctum', 'admin', 'super_admin'])->group(function () {
        Route::apiResource('admins', AdminManagementController::class);
        Route::post('admins/{admin}/deactivate', [AdminManagementController::class, 'deactivate']);
        Route::post('admins/{admin}/activate', [AdminManagementController::class, 'activate']);
    });
});

// Package Routes
Route::prefix('v1')->group(function () {
    
    // Public package routes (users & guests)
    Route::prefix('packages')->group(function () {
        Route::get('/', [UserPackageController::class, 'index']);
        Route::get('/featured', [UserPackageController::class, 'featured']);
        Route::get('/filter', [UserPackageController::class, 'filter']);
        Route::get('/search', [UserPackageController::class, 'search']);
        Route::get('/{slug}', [UserPackageController::class, 'show']);
    });

    // Admin package routes
    Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::apiResource('packages', AdminPackageController::class);
        Route::post('packages/{id}/toggle-status', [AdminPackageController::class, 'toggleStatus']);
        Route::post('packages/{id}/toggle-featured', [AdminPackageController::class, 'toggleFeatured']);
    });
});