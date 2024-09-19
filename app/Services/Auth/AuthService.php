<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use App\Enums\RoleType;
use App\Exceptions\ApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    public function login(Request $request): array
    {
        $key = 'login_attempts_' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw new ApiException("Too many login attempts. Please try again in {$seconds} seconds.", 429);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw new ApiException('Invalid credentials', 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        RateLimiter::hit($key);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ];

    }

    public function register(Request $request): array
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
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

    public function logout(): void
    {
        Auth::user()->tokens()->delete();
    }

    public function getUserData()
    {
        $userData = Auth::user();
        if (!$userData) {
            throw new ApiException('User not found', 404);
        }
        return $userData;
    }

    public function updateProfile(Request $request): User|null
    {
        $user = Auth::user();
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validatedData);
        return $user;
    }

    public function sendPasswordResetEmail(Request $request): void
    {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            throw new ApiException('Failed to send password reset link', 400);
        }
    }

    public function resetPassword(Request $request): void
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new ApiException('Failed to reset password', 400);
        }
    }

    public function enableTwoFactorAuth(): array
    {
        $user = Auth::user();
        $token = $user->createToken('2fa')->plainTextToken;
        return ['token' => $token];
    }

    public function disableTwoFactorAuth(): void
    {
        $user = Auth::user();
        $user->tokens()->where('name', '2fa')->delete();
    }

    public function verifyTwoFactorAuth(Request $request): void
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();
        $token = PersonalAccessToken::findToken($request->token);

        if (!$token || $token->tokenable_id !== $user->id || $token->name !== '2fa') {
            throw new ApiException('Invalid two-factor authentication token', 401);
        }
    }

    public function refresh(Request $request): array
    {
        $user = $request->user();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function verifyEmail(Request $request): void
    {
        $request->validate([
            'id' => 'required|integer',
            'hash' => 'required|string',
        ]);

        $user = User::findOrFail($request->id);

        if (!hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
            throw new ApiException('Invalid verification link', 400);
        }

        if ($user->hasVerifiedEmail()) {
            throw new ApiException('Email already verified', 400);
        }

        $user->markEmailAsVerified();
    }

    public function assignRole(Request $request, User $user): void
    {
        $validatedData = $request->validate([
            'role' => ['required', Rule::in(RoleType::values())],
        ]);

        $role = RoleType::from($validatedData['role']);

        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        } else {
            throw new ApiException('User already has this role', 400);
        }
    }

    public function removeRole(Request $request, User $user): void
    {
        $validatedData = $request->validate([
            'role' => ['required', Rule::in(RoleType::values())],
        ]);

        $role = RoleType::from($validatedData['role']);

        if ($user->hasRole($role)) {
            $user->removeRole($role);
        } else {
            throw new ApiException('User does not have this role', 400);
        }
    }
}
