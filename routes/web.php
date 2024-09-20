<?php

use App\Http\Controllers\AuthController;
use App\Mail\UserActivationStatus;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mailable', function () {
    $user = User::first();
    return new UserActivationStatus($user, 'activated');
});

Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

Route::get('auth/microsoft', [AuthController::class, 'redirectToMicrosoft']);
Route::get('auth/microsoft/callback', [AuthController::class, 'handleMicrosoftCallback']);
