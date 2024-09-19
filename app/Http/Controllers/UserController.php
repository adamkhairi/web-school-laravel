<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Events\UserActivationToggled;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\User\UserServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get a list of users.
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @example: GET /api/users?search=john&role=student&active=true&created_after=2023-01-01&created_before=2023-12-31&sort_by=name&sort_direction=asc&per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $users = $this->userService->getUsers($request);
            return $this->successResponse($users);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to fetch users', 500);
        }
    }

    /**
     * Get a specific user.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        try {
            return $this->successResponse($user);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve user', 500);
        }
    }

    /**
     * Create a new user.
     *
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());
            return $this->successResponse($user, 'User created successfully', 201);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to create user', 500);
        }
    }

    /**
     * Update an existing user.
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->updateUser($user, $request->validated());
            return $this->successResponse($updatedUser, 'User updated successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update user', 500);
        }
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user);
            return $this->successResponse(null, 'User deleted successfully', 204);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to delete user', 500);
        }
    }

    /**
     * Toggle user activation status.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function toggleActivation(User $user): JsonResponse
    {
        try {
            $message = $this->userService->toggleUserActivation($user);

            // Fire user activation toggled event
            event(new UserActivationToggled($user));

            return $this->successResponse($message, 200);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to toggle user activation', 500);
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
            $validatedData = $request->validate([
                'role' => ['required', Rule::in(RoleType::values())],
            ]);

            $role = RoleType::from($validatedData['role']);

            if ($this->userService->assignRole($user, $role)) {
                return $this->successResponse(null, 'Role assigned successfully');
            } else {
                return $this->successResponse(null, 'User already has this role');
            }
        } catch (Exception $e) {
            return $this->errorResponse('Failed to assign role', 500);
        }
    }

    public function removeRole(Request $request, User $user): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'role' => ['required', Rule::in(RoleType::values())],
            ]);

            $role = RoleType::from($validatedData['role']);

            $this->userService->removeRole($user, $role);
            return $this->successResponse(null, 'Role removed successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to remove role', 500);
        }
    }

    public function getUserActivity(User $user): JsonResponse
    {
        try {
            $activity = $this->userService->getUserActivity($user);
            return $this->successResponse($activity);
        } catch (Exception $e) {
            // Log the error with details
            Log::error('Failed to retrieve user activity', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            // Return a more informative error response
            return $this->errorResponse('Failed to retrieve user activity: ' . $e->getMessage(), 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            $this->userService->bulkDeleteUsers($validatedData['user_ids']);

            return $this->successResponse(null, 'Users deleted successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to delete users', 500);
        }
    }

    public function exportUsers(Request $request): StreamedResponse|JsonResponse
    {
        try {
            return $this->userService->exportUsers();
        } catch (Exception $e) {
            return $this->errorResponse('Failed to export users', 500);
        }
    }

    public function getUserStats(): JsonResponse
    {
        try {
            $stats = $this->userService->getUserStats();
            return $this->successResponse($stats);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to retrieve user stats', 500);
        }
    }
}
