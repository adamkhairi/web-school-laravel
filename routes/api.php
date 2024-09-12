<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Group routes under 'auth' prefix
Route::prefix('auth')->group(function () {
    // Existing routes
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/password/email', [AuthController::class, 'sendPasswordResetEmail'])->name('password.email');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.update');
    Route::post('/two-factor-auth/enable', [AuthController::class, 'enableTwoFactorAuth'])->name('two-factor-auth.enable');
    Route::post('/two-factor-auth/disable', [AuthController::class, 'disableTwoFactorAuth'])->name('two-factor-auth.disable');
    Route::post('/two-factor-auth/verify', [AuthController::class, 'verifyTwoFactorAuth'])->name('two-factor-auth.verify');


    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');

});

// Group routes that require authentication
Route::middleware('auth:sanctum')->group(function () {
    // Get authenticated user details
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Update user profile
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');


    Route::get('/token', [AuthController::class, 'respondWithToken'])->name('auth.token');

    // User routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
        Route::post('/{user}/toggle-activation', [UserController::class, 'toggleActivation']);
        Route::post('/{user}/assign-role', [UserController::class, 'assignRole']);
        Route::post('/{user}/remove-role', [UserController::class, 'removeRole']);
        Route::get('/{user}/activity', [UserController::class, 'getUserActivity']);
        Route::post('/bulk-delete', [UserController::class, 'bulkDelete']);
        Route::get('/export', [UserController::class, 'exportUsers']);
        Route::get('/stats', [UserController::class, 'getUserStats']);
    });
});

// Route to access a protected endpoint with 'auth:api' middleware
Route::get('/protected-endpoint', [AuthController::class, 'protectedEndpoint'])->middleware('auth:api');
