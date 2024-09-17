<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\User\UserServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;

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
            return response()->json($users);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to fetch users: ' . $e->getMessage(), 500);
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
            return response()->json($user);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to retrieve user: ' . $e->getMessage(), 500);
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
            return response()->json($user, 201);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to create user: ' . $e->getMessage(), 500);
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
            return response()->json($updatedUser);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to update user: ' . $e->getMessage(), 500);
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
            return response()->json(null, 204);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to delete user: ' . $e->getMessage(), 500);
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

            return response()->json(['message' => $message], 200);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to toggle user activation: ' . $e->getMessage(), 500);
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
                return response()->json(['message' => 'Role assigned successfully'], 200);
            } else {
                return response()->json(['message' => 'User already has this role'], 200);
            }
        } catch (Exception $e) {
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

            $this->userService->removeRole($user, $role);
            return response()->json(['message' => 'Role removed successfully'], 200);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to remove role: ' . $e->getMessage(), 500);
        }
    }

    public function getUserActivity(User $user): JsonResponse
    {
        try {
            $activity = $this->userService->getUserActivity($user);
            return response()->json($activity);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to retrieve user activity: ' . $e->getMessage(), 500);
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

            return response()->json(['message' => 'Users deleted successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendFailedResponse('Validation failed' . $e->errors(), 422);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to delete users: ' . $e->getMessage(), 500);
        }
    }

    public function exportUsers(Request $request): StreamedResponse|JsonResponse
    {
        try {
            return $this->userService->exportUsers();
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to export users: ' . $e->getMessage()], 500);
        }
    }

    public function getUserStats(): JsonResponse
    {
        try {
            $stats = $this->userService->getUserStats();
            return response()->json($stats);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve user stats: ' . $e->getMessage()], 500);
        }
    }
}
