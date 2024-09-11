<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Group routes under 'auth' prefix
Route::prefix('auth')->group(function () {
    // Route for user login
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    // Route for user registration
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    // Route for user logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    // Route to send password reset email
    Route::post('/password/email', [AuthController::class, 'sendPasswordResetEmail'])->name('password.email');
    // Route to validate password reset token
    Route::post('/password/reset', [AuthController::class, 'validatePasswordResetToken'])->name('password.reset');
    // Route to enable two-factor authentication
    Route::post('/two-factor-auth/enable', [AuthController::class, 'enableTwoFactorAuth'])->name('two-factor-auth.enable');
    // Route to disable two-factor authentication
    Route::post('/two-factor-auth/disable', [AuthController::class, 'disableTwoFactorAuth'])->name('two-factor-auth.disable');
    // Route to verify two-factor authentication
    Route::post('/two-factor-auth/verify', [AuthController::class, 'verifyTwoFactorAuth'])->name('two-factor-auth.verify');
});

// Group routes that require authentication
Route::middleware('auth:sanctum')->group(function () {
    // Route to get authenticated user details
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Route to update user profile
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
});

// Route to access a protected endpoint with 'auth:api' middleware
Route::get('/protected-endpoint', [AuthController::class, 'protectedEndpoint'])->middleware('auth:api');
