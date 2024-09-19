<?php

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
