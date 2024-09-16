<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;

class UserController extends Controller
{
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
            $query = User::query();

            // Search by name, email, or phone number
            if ($request->has('search')) {
                $searchTerm = $request->input('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%$searchTerm%")
                        ->orWhere('email', 'like', "%$searchTerm%")
                        ->orWhere('first_name', 'like', "%$searchTerm%")
                        ->orWhere('last_name', 'like', "%$searchTerm%")
                        ->orWhere('phone_number', 'like', "%$searchTerm%");
                });
            }

            // Filter by role
            if ($request->has('role')) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->input('role'));
                });
            }

            // Filter by active status
            if ($request->has('active')) {
                $query->where('is_active', $request->boolean('active'));
            }

            // Filter by creation date
            if ($request->has('created_after')) {
                $query->where('created_at', '>=', $request->input('created_after'));
            }
            if ($request->has('created_before')) {
                $query->where('created_at', '<=', $request->input('created_before'));
            }

            // Sorting
            $sortField = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Pagination
            $perPage = $request->input('per_page', 15);
            $users = $query->with('roles')->paginate($perPage);

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
            $validatedData = $request->validated();
            $validatedData['password'] = Hash::make($validatedData['password']);

            $user = User::create($validatedData);

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
            $validatedData = $request->validated();

            if (isset($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            }

            $user->update($validatedData);

            return response()->json($user);
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
            $user->delete();
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
            $user->is_active = !$user->is_active;
            $user->save();

            $message = $user->is_active ? 'User activated' : 'User deactivated';
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

    public function getUserActivity(User $user): JsonResponse
    {
        try {
            $activity = $user->activities()->latest()->take(50)->get();
            return response()->json($activity);
        } catch (\Exception $e) {
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

            User::whereIn('id', $validatedData['user_ids'])->delete();

            return response()->json(['message' => 'Users deleted successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendFailedResponse('Validation failed' . $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to delete users: ' . $e->getMessage(), 500);
        }
    }

    public function exportUsers(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $users = User::with('roles')->get();
            $csvFileName = 'users_export_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                "Content-type" => "text/csv",
                "Content-Disposition" => "attachment; filename=$csvFileName",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ];

            $columns = ['ID', 'Name', 'Email', 'Roles', 'Created At'];

            $callback = function () use ($users, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);

                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->roles->pluck('name')->implode(', '),
                        $user->created_at
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to export users: ' . $e->getMessage()], 500);
        }
    }

    public function getUserStats(): JsonResponse
    {
        try {
            $totalUsers = User::count();
            $activeUsers = User::where('is_active', true)->count();
            $usersByRole = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('roles.name', DB::raw('count(*) as count'))
                ->groupBy('roles.name')
                ->get();

            return response()->json([
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'users_by_role' => $usersByRole
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve user stats: ' . $e->getMessage()], 500);
        }
    }
}
