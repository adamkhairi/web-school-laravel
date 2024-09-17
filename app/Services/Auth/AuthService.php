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
    public function login(Request $request): JsonResponse
    {
        $key = 'login_attempts_' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw new ApiException("Too many login attempts. Please try again in {$seconds} seconds.", 429);
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            RateLimiter::clear($key);
            $user = Auth::user();
            $user->tokens()->delete();
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('sanctum.expiration') * 60,
                'user' => $user->only(['id', 'name', 'email', 'created_at', 'updated_at']),
            ]);
        }

        RateLimiter::hit($key);
        throw new ApiException('Invalid credentials', 401);
    }

    public function register(Request $request): JsonResponse
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

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user->only(['id', 'name', 'email', 'created_at', 'updated_at']),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration') * 60,
        ], 201);
    }

    public function logout(): JsonResponse
    {
        Auth::user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function getUserData(): JsonResponse
    {
        $user = Auth::user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'roles' => $user->roles->pluck('name'),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validatedData);
        return response()->json(['message' => 'Profile updated successfully.', 'user' => $user]);
    }

    public function sendPasswordResetEmail(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent to your email.']);
        }

        throw new ApiException('Failed to send password reset link. Please try again.', 400);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password has been successfully reset.']);
        }

        throw new ApiException('Failed to reset password. Please try again.', 400);
    }

    public function enableTwoFactorAuth(): JsonResponse
    {
        $user = auth()->user();
        $token = $user->createToken('2fa')->plainTextToken;
        return response()->json(['message' => 'Two-factor authentication enabled.', 'token' => $token]);
    }

    public function disableTwoFactorAuth(): JsonResponse
    {
        $user = auth()->user();
        $user->tokens()->where('name', '2fa')->delete();
        return response()->json(['message' => 'Two-factor authentication disabled.']);
    }

    public function verifyTwoFactorAuth(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'token' => 'required|string',
        ]);

        $user = auth()->user();
        $token = PersonalAccessToken::findToken($validatedData['token']);

        if ($token && $token->tokenable_id === $user->id && $token->name === '2fa') {
            return response()->json(['message' => 'Two-factor authentication successful.']);
        }
        throw new ApiException('Invalid two-factor authentication token.', 401);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->delete();
        $token = $user->createToken('api_token')->plainTextToken;
        return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
    }

    public function verifyEmail(Request $request): JsonResponse
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
        return response()->json(['message' => 'Email verified successfully']);
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'role' => ['required', Rule::in(RoleType::values())],
            ]);

            $role = RoleType::from($validatedData['role']);

            if (!$user->hasRole($role)) {
                $user->assignRole($role);
                return response()->json(['message' => 'Role assigned successfully'], 200);
            }

            return response()->json(['message' => 'User already has this role'], 200);
        } catch (ValidationException $e) {
            throw new ApiException('Invalid role provided', 422);
        } catch (\Exception $e) {
            throw new ApiException('Failed to assign role', 500);
        }
    }

    public function removeRole(Request $request, User $user): JsonResponse
    {
        $validatedData = $request->validate([
            'role' => ['required', Rule::in(RoleType::values())],
        ]);

        $role = RoleType::from($validatedData['role']);

        if ($user->hasRole($role)) {
            $user->removeRole($role);
            return response()->json(['message' => 'Role removed successfully'], 200);
        }

        return response()->json(['message' => 'User does not have this role'], 200);
    }
}
