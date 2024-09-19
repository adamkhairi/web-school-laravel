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
        Log::info('Login attempt', ['email' => $request->email]);
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
        Log::info('Registration attempt', ['email' => $request->email]);
        try {
            $result = $this->authService->register($request);
            Log::info('Registration successful', ['email' => $request->email]);
            return $this->successResponse($result, 'Registration successful', 201);
        } catch (ValidationException $e) {
            Log::error('Validation error during registration', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('API error during registration', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function logout(): JsonResponse
    {
        Log::info('Logout attempt');
        try {
            $this->authService->logout();
            Log::info('Logout successful');
            return $this->successResponse(null, 'Logout successful');
        } catch (ApiException $e) {
            Log::error('API error during logout', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function user(Request $request): JsonResponse
    {
        Log::info('User data request');
        try {
            $userData = $this->authService->getUserData();
            return $this->successResponse(['user' => $userData]);
        } catch (ApiException $e) {
            Log::error('API error retrieving user data', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        Log::info('Profile update attempt', ['email' => $request->email]);
        try {
            $result = $this->authService->updateProfile($request);
            Log::info('Profile updated successfully', ['email' => $request->email]);
            return $this->successResponse($result, 'Profile updated successfully');
        } catch (ValidationException $e) {
            Log::error('Validation error during profile update', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('API error during profile update', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    // Send password reset email
    public function sendPasswordResetEmail(Request $request): JsonResponse
    {
        Log::info('Password reset email request', ['email' => $request->email]);
        try {
            $this->authService->sendPasswordResetEmail($request);
            Log::info('Password reset email sent', ['email' => $request->email]);
            return $this->successResponse(null, 'Password reset email sent');
        } catch (ValidationException $e) {
            Log::error('Validation error during password reset email', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('API error during password reset email', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    // password reset token
    public function resetPassword(Request $request): JsonResponse
    {
        Log::info('Password reset attempt', ['email' => $request->email]);
        try {
            $this->authService->resetPassword($request);
            Log::info('Password reset successful', ['email' => $request->email]);
            return $this->successResponse(null, 'Password reset successful');
        } catch (ValidationException $e) {
            Log::error('Validation error during password reset', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('API error during password reset', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    // Two-Factor Authentication (2FA)
    public function enableTwoFactorAuth(Request $request): JsonResponse
    {
        Log::info('Enable two-factor authentication attempt');
        try {
            $result = $this->authService->enableTwoFactorAuth();
            Log::info('Two-factor authentication enabled');
            return $this->successResponse($result, 'Two-factor authentication enabled');
        } catch (ApiException $e) {
            Log::error('API error enabling two-factor authentication', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function disableTwoFactorAuth(Request $request): JsonResponse
    {
        Log::info('Disable two-factor authentication attempt');
        try {
            $this->authService->disableTwoFactorAuth();
            Log::info('Two-factor authentication disabled');
            return $this->successResponse(null, 'Two-factor authentication disabled');
        } catch (ApiException $e) {
            Log::error('API error disabling two-factor authentication', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function verifyTwoFactorAuth(Request $request): JsonResponse
    {
        Log::info('Verify two-factor authentication attempt');
        try {
            $this->authService->verifyTwoFactorAuth($request);
            Log::info('Two-factor authentication verified');
            return $this->successResponse(null, 'Two-factor authentication verified');
        } catch (ValidationException $e) {
            Log::error('Validation error during two-factor authentication verification', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('API error during two-factor authentication verification', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function protectedEndpoint(Request $request): JsonResponse
    {
        Log::info('Protected endpoint access attempt');
        try {
            $user = auth()->userOrFail();
            Log::info('Protected endpoint access granted', ['user_id' => $user->id]);
            return response()->json(['message' => 'Success! You are authorized.']);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            Log::error('Unauthorized access attempt', ['message' => 'Unauthorized']);
            return $this->sendFailedResponse('Unauthorized', 401);
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        Log::info('Token refresh attempt');
        try {
            $result = $this->authService->refresh($request);
            Log::info('Token refreshed successfully');
            return $this->successResponse($result, 'Token refreshed successfully');
        } catch (ApiException $e) {
            Log::error('API error during token refresh', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        Log::info('Email verification attempt');
        try {
            $this->authService->verifyEmail($request);
            Log::info('Email verified successfully');
            return $this->successResponse(null, 'Email verified successfully');
        } catch (ValidationException $e) {
            Log::error('Validation error during email verification', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('API error during email verification', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        Log::info('Assign role attempt', ['user_id' => $user->id]);
        try {
            $this->authService->assignRole($request, $user);
            Log::info('Role assigned successfully', ['user_id' => $user->id]);
            return $this->successResponse(null, 'Role assigned successfully');
        } catch (ValidationException $e) {
            Log::error('Validation error during role assignment', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('API error during role assignment', ['message' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function removeRole(Request $request, User $user): JsonResponse
    {
        Log::info('Remove role attempt', ['user_id' => $user->id]);
        try {
            $this->authService->removeRole($request, $user);
            Log::info('Role removed successfully', ['user_id' => $user->id]);
            return $this->successResponse(null, 'Role removed successfully');
        } catch (ValidationException $e) {
            Log::error('Validation error during role removal', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            Log::error('API error during role removal', ['message' => $e->getMessage()]);
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
