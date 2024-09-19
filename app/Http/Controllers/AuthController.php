<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Exceptions\ApiException;
use App\Services\Auth\AuthServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    protected $authService;

    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request);
            Log::info('Login successful', ['email' => $request->email]);
            return $this->successResponse($result, 'Login successful');
        } catch (ValidationException $e) {
            Log::error('Validation error during login', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('API error during login', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request);
            Log::info('Registration successful', ['email'=> $request->email]);
            return $this->successResponse($result, 'Registration successful', 201);
        } catch (ValidationException $e) {
            Log::error('Validation error during registration', ['errors'=> $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('API error during registration', ['errors'=> $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();
            Log::info('Logout successful', [ ]);
            return $this->successResponse(null, 'Logout successful');
        } catch (ApiException $e) {
            Log::error('API error during logout', ['errors'=> $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function user(Request $request): JsonResponse
    {
        try {
            $userData = $this->authService->getUserData();
            Log::info('User data retrieved', [ 'user' => $userData ]);
            return $this->successResponse(['user' => $userData]);
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->updateProfile($request);
            Log::info('Profile updated', [  'result'=> $result]);
            return $this->successResponse($result, 'Profile updated successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    // Send password reset email
    public function sendPasswordResetEmail(Request $request): JsonResponse
    {
        try {
            $this->authService->sendPasswordResetEmail($request);
    Log::info(' Password reset email sent', [  'email'=> $request->email]); 
            return $this->successResponse(null, 'Password reset email sent');
        } catch (ValidationException $e) {
            Log::error('Validation error during password reset', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('API error during password reset', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    // password reset token
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $this->authService->resetPassword($request);
            Log::info('Password reset successful', [ 'email'=> $request->email]);
            return $this->successResponse(null, 'Password reset successful');
        } catch (ValidationException $e) {
            Log::error('Validation error during password reset', ['errors'=> $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('API error during password reset', ['errors'=> $e->errors()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

/*     public function respondWithToken($token): JsonResponse
    {
        $user = auth()->user();
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('sanctum.expiration') * 60,
            'user' => $user->only(['id', 'name', 'email', 'created_at', 'updated_at'])
        ]);
    } */

    // Two-Factor Authentication (2FA)
    public function enableTwoFactorAuth(Request $request): JsonResponse
    {
        try {
            Log::info('Enabling two-factor authentication', ['user_id' => auth()->id()]);
            $result = $this->authService->enableTwoFactorAuth();
            return $this->successResponse($result, 'Two-factor authentication enabled');
        } catch (ApiException $e) {
            Log::error('Failed to enable two-factor authentication', ['error' => $e->getMessage(), 'user_id' => auth()->id()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function disableTwoFactorAuth(Request $request): JsonResponse
    {
        try {
            Log::info('Disabling two-factor authentication', ['user_id' => auth()->id()]);
            $this->authService->disableTwoFactorAuth();
            return $this->successResponse(null, 'Two-factor authentication disabled');
        } catch (ApiException $e) {
            Log::error('Failed to disable two-factor authentication', ['error' => $e->getMessage(), 'user_id' => auth()->id()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function verifyTwoFactorAuth(Request $request): JsonResponse
    {
        try {
            Log::info('Verifying two-factor authentication', ['user_id' => auth()->id()]);
            $this->authService->verifyTwoFactorAuth($request);
            return $this->successResponse(null, 'Two-factor authentication verified');
        } catch (ValidationException $e) {
            Log::error('Validation error during two-factor authentication verification', ['errors' => $e->errors(), 'user_id' => auth()->id()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('Failed to verify two-factor authentication', ['error' => $e->getMessage(), 'user_id' => auth()->id()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function protectedEndpoint(Request $request): JsonResponse
    {
        try {
            $user = auth()->userOrFail();
            Log::info('Accessing protected endpoint', ['user_id' => $user->id]);
            return response()->json(['message' => 'Success! You are authorized.']);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            Log::warning('Unauthorized access attempt', ['error' => $e->getMessage(), 'ip' => $request->ip()]);
            return $this->sendFailedResponse('Unauthorized', 401);
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            Log::info('Refreshing token', ['user_id' => auth()->id()]);
            $result = $this->authService->refresh($request);
            return $this->successResponse($result, 'Token refreshed successfully');
        } catch (ApiException $e) {
            Log::error('Failed to refresh token', ['error' => $e->getMessage(), 'user_id' => auth()->id()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        try {
            Log::info('Verifying email', ['user_id' => auth()->id()]);
            $this->authService->verifyEmail($request);
            return $this->successResponse(null, 'Email verified successfully');
        } catch (ValidationException $e) {
            Log::error('Validation error during email verification', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('Failed to verify email', ['error' => $e->getMessage(), 'user_id' => auth()->id()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        try {
            Log::info('Assigning role', ['user_id' => $user->id, 'role' => $request->input('role')]);
            $this->authService->assignRole($request, $user);
            return $this->successResponse(null, 'Role assigned successfully');
        } catch (ValidationException $e) {
            Log::error('Validation error during role assignment', ['errors' => $e->errors(), 'user_id' => $user->id]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('Failed to assign role', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function removeRole(Request $request, User $user): JsonResponse
    {
        try {
            Log::info('Removing role from user ', ['user_id' => $user->id, 'role' => $request->input('role')]);
            $this->authService->removeRole($request, $user);
            return $this->successResponse(null, 'Role removed successfully');
        } catch (ValidationException $e) {
            Log::error('Validation error during role removal', ['errors' => $e->errors(), 'user_id' => $user->id]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('Failed to remove role', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }


    // 9. Social Authentication
    // You'll need to install and configure the appropriate social authentication packages
    // and follow their documentation for implementing social authentication.


    // 10. API Documentation
    // You can use tools like Swagger or Postman to document your API endpoints.
    // This typically involves creating a separate documentation file or using annotations in your code.

}
