<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;
use App\Enums\RoleType;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{

    public function login(Request $request): JsonResponse
    {
        $key = 'login_attempts_' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return $this->sendFailedResponse("Too many login attempts. Please try again in {$seconds} seconds.", 429);
        }

        try {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            if (Auth::attempt($credentials)) {
                RateLimiter::clear($key);
                $user = Auth::user();

                // Revoke all existing tokens
                $user->tokens()->delete();

                // Create a new token
                $token = $user->createToken('api-token')->plainTextToken;

                return response()->json([
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => config('sanctum.expiration') * 60,
                    'user' => $user->only(['id', 'name', 'email', 'created_at', 'updated_at']),
                ], 200);
            }

            RateLimiter::hit($key);
            return $this->sendFailedResponse('Invalid credentials', 401);
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (\Exception $e) {
            report($e);
            return $this->sendFailedResponse('An unexpected error occurred. Please try again.', 500);
        }
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
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
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (\Exception $e) {
            report($e);
            return $this->sendFailedResponse('Failed to register user. Please try again.', 500);
        }
    }

    // Two-Factor Authentication (2FA)
    public function enableTwoFactorAuth(Request $request): JsonResponse
    {
        $user = auth()->user();

        $token = $user->createToken('2fa')->plainTextToken;

        return response()->json(['message' => 'Two-factor authentication enabled.', 'token' => $token]);
    }

    public function disableTwoFactorAuth(Request $request): JsonResponse
    {
        $user = auth()->user();

        $user->tokens()->delete();

        return response()->json(['message' => 'Two-factor authentication disabled.']);
    }

    public function verifyTwoFactorAuth(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'token' => 'required|string',
        ]);

        $user = auth()->user();
        $token = PersonalAccessToken::findToken($validatedData['token']);

        if ($token && $token->tokenable_id === $user->id) {
            return response()->json(['message' => 'Two-factor authentication successful.']);
        }
        return $this->sendFailedResponse('Invalid two-factor authentication token.', 401);
    }

    public function logout(): JsonResponse
    {
        try {
            $token = JWTAuth::parseToken();
            $token->invalidate();
            auth()->logout();

            return response()->json(['message' => 'Logged out successfully.'], 200);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->sendFailedResponse('You are already logged out.', 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return $this->sendFailedResponse('Failed to logout. Please try again.', 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->sendFailedResponse('User not authenticated.', 401);
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            ]);

            $user->update($validatedData);

            return response()->json(['message' => 'Profile updated successfully.', 'user' => $user], 200);
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to update profile. Please try again.', 500);
        }
    }

    // Send password reset email
    public function sendPasswordResetEmail(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate(['email' => 'required|email']);

            $status = Password::sendResetLink($validatedData);

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json(['message' => 'Password reset link sent to your email.'], 200);
            } else {
                return $this->sendFailedResponse('Failed to send password reset link. Please try again.', 400);
            }
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('An unexpected error occurred. Please try again.', 500);
        }
    }

    // password reset token
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed',
            ]);

            $status = Password::reset(
                $validatedData,
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json(['message' => 'Password has been successfully reset.'], 200);
            } else {
                return $this->sendFailedResponse('Failed to reset password. Please try again.', 400);
            }
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('An unexpected error occurred. Please try again.', 500);
        }
    }

    // 9. Social Authentication
    // You'll need to install and configure the appropriate social authentication packages
    // and follow their documentation for implementing social authentication.


    // 10. API Documentation
    // You can use tools like Swagger or Postman to document your API endpoints.
    // This typically involves creating a separate documentation file or using annotations in your code.

    public function respondWithToken($token): JsonResponse
    {
        $user = auth()->user();
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('sanctum.expiration') * 60,
            'user' => $user->only(['id', 'name', 'email', 'created_at', 'updated_at'])
        ]);
    }

    public function protectedEndpoint(Request $request): JsonResponse
    {
        try {
            $user = auth()->userOrFail();
            return response()->json(['message' => 'Success! You are authorized.']);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return $this->sendFailedResponse('Unauthorized', 401);
        }
    }

    public function refresh(): JsonResponse
    {
        try {

            $user = auth()->user();
            if (!$user) {
                return $this->sendFailedResponse('Unauthorized', 401);
            }

            // Revoke all tokens...
            $user->tokens()->delete();

            $newToken =  $user->createToken('api-token')->plainTextToken;
            return $this->respondWithToken($newToken);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->sendFailedResponse('Token is invalid', 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->sendFailedResponse('Token has expired', 401);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Token could not be parsed from the request', 401);
        }
    }


    public function verifyEmail(Request $request): JsonResponse
    {
        $user = User::find($request->route('id'));

        if (!hash_equals((string)$request->route('hash'), sha1($user->getEmailForVerification()))) {
            return $this->sendFailedResponse('Invalid verification link', 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email verified successfully'], 200);
    }

    /**
     * Assign a role to a user.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
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
            } else {
                return response()->json(['message' => 'User already has this role'], 200);
            }
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to assign role: ' . $e->getMessage(), 500);
        }
    }

    public function removeRole(Request $request, User $user): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'role' => ['required', Rule::in(RoleType::values())],
            ]);

            $role = RoleType::from($validatedData['role']);

            $user->roles()->detach(Role::where('name', $role->value)->firstOrFail()->id);

            return response()->json(['message' => 'Role removed successfully'], 200);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to remove role: ' . $e->getMessage(), 500);
        }
    }
}
