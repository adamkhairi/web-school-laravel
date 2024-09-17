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
            return response()->json($result, 200);
        } catch (ApiException $e) {
            return $this->sendFailedResponse($e->getMessage(), $e->getCode());
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (Exception $e) {
            report($e);
            return $this->sendFailedResponse('An unexpected error occurred. Please try again.', 500);
        }
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request);
            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (Exception $e) {
            report($e);
            return $this->sendFailedResponse('Failed to register user. Please try again.', 500);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $result = $this->authService->logout();
            return response()->json($result, 200);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->sendFailedResponse('You are already logged out.', 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return $this->sendFailedResponse('Failed to logout. Please try again.', 500);
        }
    }

    public function user(Request $request): JsonResponse
    {
        try {
            $userData = $this->authService->getUserData();
            return response()->json(['user' => $userData], 200);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to retrieve user data. Please try again.', 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->updateProfile($request);
            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to update profile. Please try again.', 500);
        }
    }

    // Send password reset email
    public function sendPasswordResetEmail(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->sendPasswordResetEmail($request);
            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (ApiException $e) {
            return $this->sendFailedResponse($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            return $this->sendFailedResponse('An unexpected error occurred. Please try again.', 500);
        }
    }

    // password reset token
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->resetPassword($request);
            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (ApiException $e) {
            return $this->sendFailedResponse($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            return $this->sendFailedResponse('An unexpected error occurred. Please try again.', 500);
        }
    }

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

    // Two-Factor Authentication (2FA)
    public function enableTwoFactorAuth(Request $request): JsonResponse
    {
        return $this->authService->enableTwoFactorAuth();
    }

    public function disableTwoFactorAuth(Request $request): JsonResponse
    {
        return $this->authService->disableTwoFactorAuth();
    }

    public function verifyTwoFactorAuth(Request $request): JsonResponse
    {
        try {
            return $this->authService->verifyTwoFactorAuth($request);
        } catch (ApiException $e) {
            return $this->sendFailedResponse($e->getMessage(), $e->getCode());
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

    public function refresh(Request $request)
    {
        try {
            return $this->authService->refresh($request);
        } catch (Exception $e) {
            throw new ApiException('Failed to refresh token', 401);
        }
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        try {
            return $this->authService->verifyEmail($request);
        } catch (ApiException $e) {
            return $this->sendFailedResponse($e->getMessage(), $e->getCode());
        }
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
            return $this->authService->assignRole($request, $user);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to assign role: ' . $e->getMessage(), 500);
        }
    }

    public function removeRole(Request $request, User $user): JsonResponse
    {
        try {
            return $this->authService->removeRole($request, $user);
        } catch (ValidationException $e) {
            throw new ApiException('Invalid role provided', 422);
        } catch (Exception $e) {
            throw new ApiException('Failed to remove role', 500);
        }
    }


    // 9. Social Authentication
    // You'll need to install and configure the appropriate social authentication packages
    // and follow their documentation for implementing social authentication.


    // 10. API Documentation
    // You can use tools like Swagger or Postman to document your API endpoints.
    // This typically involves creating a separate documentation file or using annotations in your code.

}
