<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Str;

class OAuthService implements OAuthServiceInterface
{
    public function handleGoogleCallback($googleUser): User
    {
        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            // Register the user if they don't exist
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'password' => Hash::make(Str::random(16)), // Random password for OAuth users
            ]);
        }

        // Log the user in
        Auth::login($user);
        return $user;
    }

    public function handleMicrosoftCallback($microsoftUser): User
    {
        $user = User::where('email', $microsoftUser->getEmail())->first();

        if (!$user) {
            // Register the user if they don't exist
            $user = User::create([
                'name' => $microsoftUser->getName(),
                'email' => $microsoftUser->getEmail(),
                'password' => Hash::make(Str::random(16)), // Random password for OAuth users
            ]);
        }

        // Log the user in
        Auth::login($user);
        return $user;
    }
}
