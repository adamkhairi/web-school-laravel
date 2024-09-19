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
        Log::info('Fetching users', ['request' => $request->all()]);
        try {
            $users = $this->userService->getUsers($request);
            Log::info('Users fetched successfully', ['users_count' => count($users)]);
            return $this->successResponse($users);
        } catch (Exception $e) {
            Log::error('Failed to fetch users', ['error' => $e->getMessage()]);
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
        Log::info('Fetching user', ['user_id' => $user->id]);
        try {
            return $this->successResponse($user);
        } catch (Exception $e) {
            Log::error('Failed to retrieve user', ['user_id' => $user->id, 'error' => $e->getMessage()]);
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
        Log::info('Creating a new user', ['request' => $request->validated()]);
        try {
            $user = $this->userService->createUser($request->validated());
            Log::info('User created successfully', ['user_id' => $user->id]);
            return $this->successResponse($user, 'User created successfully', 201);
        } catch (Exception $e) {
            Log::error('Failed to create user', ['error' => $e->getMessage()]);
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
        Log::info('Updating user', ['user_id' => $user->id, 'request' => $request->validated()]);
        try {
            $updatedUser = $this->userService->updateUser($user, $request->validated());
            Log::info('User updated successfully', ['user_id' => $updatedUser->id]);
            return $this->successResponse($updatedUser, 'User updated successfully');
        } catch (Exception $e) {
            Log::error('Failed to update user', ['user_id' => $user->id, 'error' => $e->getMessage()]);
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
        Log::info('Deleting user', ['user_id' => $user->id]);
        try {
            $this->userService->deleteUser($user);
            Log::info('User deleted successfully', ['user_id' => $user->id]);
            return $this->successResponse(null, 'User deleted successfully', 204);
        } catch (Exception $e) {
            Log::error('Failed to delete user', ['user_id' => $user->id, 'error' => $e->getMessage()]);
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
        Log::info('Toggling user activation', ['user_id' => $user->id]);
        try {
            $message = $this->userService->toggleUserActivation($user);
            event(new UserActivationToggled($user));
            Log::info('User activation toggled successfully', ['user_id' => $user->id]);
            return $this->successResponse($message, 200);
        } catch (Exception $e) {
            Log::error('Failed to toggle user activation', ['user_id' => $user->id, 'error' => $e->getMessage()]);
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
        Log::info('Assigning role to user', ['user_id' => $user->id, 'request' => $request->all()]);
        try {
            $validatedData = $request->validate([
                'role' => ['required', Rule::in(RoleType::values())],
            ]);

            $role = RoleType::from($validatedData['role']);

            if ($this->userService->assignRole($user, $role)) {
                Log::info('Role assigned successfully', ['user_id' => $user->id, 'role' => $role]);
                return $this->successResponse(null, 'Role assigned successfully');
            } else {
                Log::info('User already has this role', ['user_id' => $user->id, 'role' => $role]);
                return $this->successResponse(null, 'User already has this role');
            }
        } catch (Exception $e) {
            Log::error('Failed to assign role', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return $this->errorResponse('Failed to assign role', 500);
        }
    }

    public function removeRole(Request $request, User $user): JsonResponse
    {
        Log::info('Removing role from user', ['user_id' => $user->id, 'request' => $request->all()]);
        try {
            $validatedData = $request->validate([
                'role' => ['required', Rule::in(RoleType::values())],
            ]);

            $role = RoleType::from($validatedData['role']);

            $this->userService->removeRole($user, $role);
            Log::info('Role removed successfully', ['user_id' => $user->id, 'role' => $role]);
            return $this->successResponse(null, 'Role removed successfully');
        } catch (Exception $e) {
            Log::error('Failed to remove role', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return $this->errorResponse('Failed to remove role', 500);
        }
    }

    public function getUserActivity(User $user): JsonResponse
    {
        Log::info('Fetching user activity', ['user_id' => $user->id]);
        try {
            $activity = $this->userService->getUserActivity($user);
            Log::info('User activity retrieved successfully', ['user_id' => $user->id]);
            return $this->successResponse($activity);
        } catch (Exception $e) {
            Log::error('Failed to retrieve user activity', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Failed to retrieve user activity: ' . $e->getMessage(), 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        Log::info('Bulk deleting users', ['request' => $request->all()]);
        try {
            $validatedData = $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            $this->userService->bulkDeleteUsers($validatedData['user_ids']);
            Log::info('Users deleted successfully', ['user_ids' => $validatedData['user_ids']]);
            return $this->successResponse(null, 'Users deleted successfully');
        } catch (Exception $e) {
            Log::error('Failed to delete users', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to delete users', 500);
        }
    }

    public function exportUsers(Request $request): StreamedResponse|JsonResponse
    {
        Log::info('Exporting users');
        try {
            $response = $this->userService->exportUsers();
            Log::info('Users exported successfully');
            return $response;
        } catch (Exception $e) {
            Log::error('Failed to export users', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to export users', 500);
        }
    }

    public function getUserStats(): JsonResponse
    {
        Log::info('Fetching user stats');
        try {
            $stats = $this->userService->getUserStats();
            Log::info('User stats retrieved successfully');
            return $this->successResponse($stats);
        } catch (Exception $e) {
            Log::error('Failed to retrieve user stats', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to retrieve user stats', 500);
        }
    }
}
