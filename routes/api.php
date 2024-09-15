<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\StudyController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::prefix('auth')->group(function () {
    // User Authentication
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

    // User Profile
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // This route returns the authenticated user
    Route::get('/user', [AuthController::class, 'user'])->name('auth.user')->middleware('auth:sanctum');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh')->middleware('auth:sanctum');

    // Password Reset
    Route::post('/password/email', [AuthController::class, 'sendPasswordResetEmail'])->name('password.email');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');

    // Two-Factor Authentication
    Route::post('/two-factor-auth/enable', [AuthController::class, 'enableTwoFactorAuth'])->name('auth.two-factor.enable');
    Route::post('/two-factor-auth/disable', [AuthController::class, 'disableTwoFactorAuth'])->name('auth.two-factor.disable');
    Route::post('/two-factor-auth/verify', [AuthController::class, 'verifyTwoFactorAuth'])->name('auth.two-factor.verify');

    // Email Verification
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
});
// Protected Routes (Require Authentication)
Route::middleware('auth:sanctum')->group(function () {

    // User Management
    Route::prefix('users')->group(function () {
        // CRUD Operations
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::post('/', [UserController::class, 'store'])->name('users.store');
        Route::get('/{user}', [UserController::class, 'show'])->name('users.show');
        Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // User Actions
        Route::post('/{user}/toggle-activation', [UserController::class, 'toggleActivation'])->name('users.toggleActivation');
        Route::post('/{user}/assign-role', [UserController::class, 'assignRole'])->name('users.assignRole');
        Route::post('/{user}/remove-role', [UserController::class, 'removeRole'])->name('users.removeRole');
        Route::get('/{user}/activity', [UserController::class, 'getUserActivity'])->name('users.activity');

        // Bulk Operations
        Route::post('/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');

        // Reporting and Statistics
        Route::get('/export', [UserController::class, 'exportUsers'])->name('users.export');
        Route::get('/stats', [UserController::class, 'getUserStats'])->name('users.stats');
    });
    // Course Management
    Route::prefix('courses')->group(function () {
        Route::get('/', [CourseController::class, 'index'])->name('courses.index');
        Route::post('/', [CourseController::class, 'store'])->name('courses.store');
        Route::get('/{course}', [CourseController::class, 'show'])->name('courses.show');
        Route::put('/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    });

    // Class Management
    Route::prefix('classes')->group(function () {
        Route::get('/', [StudyController::class, 'index'])->name('classes.index');
        Route::post('/', [StudyController::class, 'store'])->name('classes.store');
        Route::get('/{class}', [StudyController::class, 'show'])->name('classes.show');
        Route::put('/{class}', [StudyController::class, 'update'])->name('classes.update');
        Route::delete('/{class}', [StudyController::class, 'destroy'])->name('classes.destroy');
    });
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    // Admin routes
});

Route::middleware(['auth', 'role:teacher'])->group(function () {
    // Teacher routes
});

Route::middleware(['auth', 'role:student'])->group(function () {
    // Student routes
});


// Protected Endpoint (Using auth:api middleware)
Route::get('/protected-endpoint', [AuthController::class, 'protectedEndpoint'])->middleware('auth:api');
