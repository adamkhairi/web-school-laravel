<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use App\Enums\RoleType;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\RateLimiter;

class AuthService
{

  public function login(array $credentials)
  {
    $key = 'login_attempts_' . $request->ip();

    if (RateLimiter::tooManyAttempts($key, 5)) {
      $seconds = RateLimiter::availableIn($key);
      throw new ApiException("Too many login attempts. Please try again in {$seconds} seconds.", 429);
    }

    if (Auth::attempt($credentials)) {
      RateLimiter::clear($key);
      $user = Auth::user();
      $user->tokens()->delete();
      $token = $user->createToken('api-token')->plainTextToken;

      return [
        'token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => config('sanctum.expiration') * 60,
        'user' => $user->only(['id', 'name', 'email', 'created_at', 'updated_at']),
      ];
    }
    RateLimiter::hit($key);
    throw new ApiException('Invalid credentials', 401);
  }

  public function register(array $data)
  {
    $user = User::create([
      'name' => $data['name'],
      'email' => $data['email'],
      'password' => Hash::make($data['password']),
    ]);

    $token = $user->createToken('api-token')->plainTextToken;

    return [
      'message' => 'User successfully registered',
      'user' => $user->only(['id', 'name', 'email', 'created_at', 'updated_at']),
      'token' => $token,
      'token_type' => 'Bearer',
      'expires_in' => config('sanctum.expiration') * 60,
    ];
  }

  public function logout()
  {
    Auth::user()->tokens()->delete();
    return ['message' => 'Logged out successfully.'];
  }

  public function getUserData()
  {
    $user = Auth::user();
    return [
      'id' => $user->id,
      'name' => $user->name,
      'email' => $user->email,
      'created_at' => $user->created_at,
      'updated_at' => $user->updated_at,
      'roles' => $user->roles->pluck('name'),
    ];
  }

  public function updateProfile(array $data)
  {
    $user = Auth::user();
    $user->update($data);
    return ['message' => 'Profile updated successfully.', 'user' => $user];
  }

  public function sendPasswordResetEmail(string $email)
  {
    $status = Password::sendResetLink(['email' => $email]);

    if ($status === Password::RESET_LINK_SENT) {
      return ['message' => 'Password reset link sent to your email.'];
    }

    throw new ApiException('Failed to send password reset link. Please try again.', 400);
  }

  public function resetPassword(array $data)
  {
    $status = Password::reset(
      $data,
      function (User $user, string $password) {
        $user->forceFill([
          'password' => Hash::make($password)
        ])->setRememberToken(Str::random(60));

        $user->save();
      }
    );

    if ($status === Password::PASSWORD_RESET) {
      return ['message' => 'Password has been successfully reset.'];
    }

    throw new ApiException('Failed to reset password. Please try again.', 400);
  }

  // Implement other methods as needed...
}
