<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    // 5. Error Handling and Validation
    protected function sendFailedResponse($message)
    {
        return response()->json(['error' => $message], 422);
    }

    // 6. Rate Limiting
    public function login(Request $request): JsonResponse
    {
        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->sendFailedResponse("Too many attempts. Please try again in $seconds seconds.");
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = JWTAuth::fromUser($user);

            RateLimiter::hit($throttleKey);

            return response()->json(compact('token'));
        }

        RateLimiter::hit($throttleKey, 60); // 1 minute decay

        return $this->sendFailedResponse('Invalid credentials');
    }

    // 7. Email Verification
    public function register(Request $request): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create a new user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'email_verified_at' => null, // Set email_verified_at to null initially
        ]);
        Log::info('New user created: ', (array) $user);

        // Generate a JWT token for the new user
        $token = JWTAuth::fromUser($user);

        // Send email verification link
        // $user->sendEmailVerificationNotification();

        // Return the token
        return response()->json(compact('token'));
    }

    // 8. Two-Factor Authentication (2FA)
    public function enableTwoFactorAuth(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Generate a new personal access token for 2FA
        $token = $user->createToken('2fa')->plainTextToken;

        // Store the token in the user's session or send it via email/SMS

        return response()->json(['message' => 'Two-factor authentication enabled.', 'token' => $token]);
    }

    public function disableTwoFactorAuth(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Revoke all personal access tokens for the user
        $user->tokens()->delete();

        return response()->json(['message' => 'Two-factor authentication disabled.']);
    }

    public function verifyTwoFactorAuth(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Verify the provided token against the user's personal access tokens
        $token = PersonalAccessToken::findToken($request->input('token'));

        if ($token && $token->tokenable_id === $user->id) {
            // Two-factor authentication successful
            return response()->json(['message' => 'Two-factor authentication successful.']);
        }

        return response()->json(['error' => 'Invalid two-factor authentication token.'], 401);
    }

    public function logout(): JsonResponse
    {
        try {
            // Get the token from the request
            $token = JWTAuth::getToken();
    
            // Invalidate the token
            JWTAuth::invalidate($token);
    
            return response()->json(['message' => 'Logged out successfully.']);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            // If the token is invalid, it means the user is already logged out
            return response()->json(['message' => 'You are already logged out.']);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($validatedData);

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    // Send password reset email
    public function sendPasswordResetEmail(Request $request): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validate(['email' => 'required|email']);

        // Send the password reset email
        $response = Password::sendResetLink($validatedData['email']);

        if ($response === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent to your email.']);
        } else {
            return response()->json(['error' => 'Failed to send password reset link.'], 422);
        }
    }

    // Validate password reset token
    public function validatePasswordResetToken(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
        ]);

        $status = Password::reset(
            $validatedData,
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully.']);
        } else {
            return response()->json(['error' => 'Failed to reset password.'], 422);
        }
    }


    // 9. Social Authentication
    // You'll need to install and configure the appropriate social authentication packages
    // and follow their documentation for implementing social authentication.


    // 10. API Documentation
    // You can use tools like Swagger or Postman to document your API endpoints.
    // This typically involves creating a separate documentation file or using annotations in your code.


    public function protectedEndpoint(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token is missing'], 401);
        }

        // Access user data or perform actions requiring authentication
        return response()->json(['message' => 'Success! You are authorized.']);
    }
}
