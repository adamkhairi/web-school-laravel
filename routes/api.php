<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    Route::post('/login', [AuthController::class, 'login'])->name('auth.login'); // Login endpoint

    Route::post('/register', [AuthController::class, 'register'])->name('auth.register'); // Login endpoint


});


// Route::get('/protected-endpoint', 'AuthController@protectedEndpoint');
// Requires JWT token in request header for access

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
