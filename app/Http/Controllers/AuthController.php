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

class AuthController extends Controller
{
    // Error Handling and Validation
    protected function sendFailedResponse($message, $status): JsonResponse
    {
        return response()->json(['error' => $message], $status);
    }

    // Rate Limiting
    public function login(Request $request): JsonResponse
    {
        $key = 'login_attempts_' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return $this->sendFailedResponse("Too many login attempts. Please try again in {$seconds} seconds.", 429);
        }

        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (Auth::attempt($credentials)) {
                RateLimiter::clear($key);
                $user = Auth::user();
                $token = $user->createToken('api-token')->plainTextToken;

                return response()->json([
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => config('sanctum.expiration') * 60, // Convert minutes to seconds
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ]
                ], 200);
            }

            RateLimiter::hit($key);
            return $this->sendFailedResponse('Invalid credentials', 401);
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);

        } catch (\Exception $e) {
            return $this->sendFailedResponse('An unexpected error occurred. Please try again.', 500);
        }
    }

    // Email Verification
    public function register(Request $request): JsonResponse
    {
        try {
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

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'message' => 'User successfully registered',
                'user' => $user,
                'token' => $token,
            ], 201);
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (\Exception $e) {
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

    protected function respondWithToken($token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
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
        return $this->respondWithToken(auth()->refresh());
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
}
