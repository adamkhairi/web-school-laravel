<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::prefix('auth')->group(function () {
    // User Authentication
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    });

    // Password Reset
    Route::post('/password/email', [AuthController::class, 'sendPasswordResetEmail'])->name('password.email');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.update');

    // Two-Factor Authentication
    Route::post('/two-factor-auth/enable', [AuthController::class, 'enableTwoFactorAuth'])->name('two-factor-auth.enable');
    Route::post('/two-factor-auth/disable', [AuthController::class, 'disableTwoFactorAuth'])->name('two-factor-auth.disable');
    Route::post('/two-factor-auth/verify', [AuthController::class, 'verifyTwoFactorAuth'])->name('two-factor-auth.verify');

    // Email Verification
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
});

// Protected Routes (Require Authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Current User Profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // User Management
    Route::prefix('users')->group(function () {

        // CRUD Operations
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);

        // User Actions
        Route::post('/{user}/toggle-activation', [UserController::class, 'toggleActivation']);
        Route::post('/{user}/assign-role', [UserController::class, 'assignRole']);
        Route::post('/{user}/remove-role', [UserController::class, 'removeRole']);
        Route::get('/{user}/activity', [UserController::class, 'getUserActivity']);

        // Bulk Operations
        Route::post('/bulk-delete', [UserController::class, 'bulkDelete']);

        // Reporting and Statistics
        Route::get('/export', [UserController::class, 'exportUsers']);
        Route::get('/stats', [UserController::class, 'getUserStats']);
    });
});

// Admin Dashboard (Using role middleware)
Route::get('/admin', function () {
    // Admin only

})->middleware('role:Admin');


// Protected Endpoint (Using auth:api middleware)
Route::get('/protected-endpoint', [AuthController::class, 'protectedEndpoint'])->middleware('auth:api');
