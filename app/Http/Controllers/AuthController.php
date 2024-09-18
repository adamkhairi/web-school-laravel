<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Exceptions\ApiException;
use App\Services\Auth\AuthServiceInterface;
use Exception;

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
            return $this->successResponse($result, 'Login successful');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request);
            return $this->successResponse($result, 'Registration successful', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();
            return $this->successResponse(null, 'Logout successful');
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function user(Request $request): JsonResponse
    {
        try {
            $userData = $this->authService->getUserData();
            return $this->successResponse(['user' => $userData]);
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->updateProfile($request);
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
            return $this->successResponse(null, 'Password reset email sent');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    // password reset token
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $this->authService->resetPassword($request);
            return $this->successResponse(null, 'Password reset successful');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
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
            $result = $this->authService->enableTwoFactorAuth();
            return $this->successResponse($result, 'Two-factor authentication enabled');
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function disableTwoFactorAuth(Request $request): JsonResponse
    {
        try {
            $this->authService->disableTwoFactorAuth();
            return $this->successResponse(null, 'Two-factor authentication disabled');
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function verifyTwoFactorAuth(Request $request): JsonResponse
    {
        try {
            $this->authService->verifyTwoFactorAuth($request);
            return $this->successResponse(null, 'Two-factor authentication verified');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
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

    public function refresh(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->refresh($request);
            return $this->successResponse($result, 'Token refreshed successfully');
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        try {
            $this->authService->verifyEmail($request);
            return $this->successResponse(null, 'Email verified successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        try {
            $this->authService->assignRole($request, $user);
            return $this->successResponse(null, 'Role assigned successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function removeRole(Request $request, User $user): JsonResponse
    {
        try {
            $this->authService->removeRole($request, $user);
            return $this->successResponse(null, 'Role removed successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (ApiException $e) {
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
