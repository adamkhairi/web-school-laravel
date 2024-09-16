<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;
use App\Enums\RoleType;
use App\Exceptions\ApiException;
use App\Models\Role;
use App\Services\AuthService;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            $result = $this->authService->login($credentials);
            return response()->json($result, 200);
        } catch (ApiException $e) {
            return $this->sendFailedResponse($e->getMessage(), $e->getCode());
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

            $result = $this->authService->register($validatedData);
            return response()->json($result, 201);
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
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to retrieve user data. Please try again.', 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            ]);

            $result = $this->authService->updateProfile($validatedData);
            return response()->json($result, 200);
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
            $result = $this->authService->sendPasswordResetEmail($validatedData['email']);
            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (ApiException $e) {
            return $this->sendFailedResponse($e->getMessage(), $e->getCode());
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

            $result = $this->authService->resetPassword($validatedData);
            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (ApiException $e) {
            return $this->sendFailedResponse($e->getMessage(), $e->getCode());
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

    public function refresh(Request $request)
    {
        try {
            $user = $request->user();
            $user->tokens()->delete();
            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (\Exception $e) {
            throw new ApiException('Failed to refresh token', 401);
        }
    }


    public function verifyEmail(Request $request): JsonResponse
    {
        $user = User::find($request->route('id'));

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
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
