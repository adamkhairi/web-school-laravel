<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\Role;
use App\Enums\RoleType;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserService implements UserServiceInterface
{
  public function getUsers($request)
  {
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
    return $query->with('roles')->paginate($perPage);
  }

  public function createUser($data)
  {
    $data['password'] = Hash::make($data['password']);
    return User::create($data);
  }

  public function updateUser(User $user, $data)
  {
    if (isset($data['password'])) {
      $data['password'] = Hash::make($data['password']);
    }
    $user->update($data);
    return $user;
  }

  public function deleteUser(User $user)
  {
    return $user->delete();
  }

  public function toggleUserActivation(User $user)
  {
    $user->is_active = !$user->is_active;
    $user->save();
    return $user->is_active ? 'User activated' : 'User deactivated';
  }

  public function assignRole(User $user, RoleType $role): bool
  {
    if ($user->hasRole($role)) {
      return false;
    }
    $user->assignRole($role);
    return true;
  }

  public function removeRole(User $user, RoleType $role)
  {
    if (!$user->hasRole($role)) {
      throw new ApiException('User does not have this role', 400);
    }
    $user->removeRole($role);
  }

  //TODO: Fix and Implement user activity retrieval logic
  public function getUserActivity(User $user)
  {

    // This is a placeholder and should be replaced with actual logic
    return [
      'last_login' => $user->last_login,
      'course_progress' => $user->courseProgress,
      'total_courses' => $user->totalCourses,
      'total_activities' => $user->totalActivities,
      'total_time' => $user->totalTime,
      // Add more activity data as needed
    ];
    //return $user->activities()->latest()->take(50)->get();
  }

  public function bulkDeleteUsers($userIds)
  {
    return DB::transaction(function () use ($userIds) {
      return User::whereIn('id', $userIds)->delete();
    });
  }

  public function exportUsers(): StreamedResponse
  {
    $headers = [
      "Content-type" => "text/csv",
      "Content-Disposition" => "attachment; filename=users.csv",
      "Pragma" => "no-cache",
      "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
      "Expires" => "0"
    ];

    $callback = function () {
      $file = fopen('php://output', 'w');
      fputcsv($file, ['ID', 'Name', 'Email', 'Created At']);

      User::chunk(1000, function ($users) use ($file) {
        foreach ($users as $user) {
          fputcsv($file, [$user->id, $user->name, $user->email, $user->created_at]);
        }
      });

      fclose($file);
    };

    return response()->stream($callback, 200, $headers);
  }


  public function addRole(array $data)
  {
    $validatedData = Validator::make($data, [
      'name' => 'required|string|unique:roles,name',
      'description' => 'nullable|string',
    ])->validate();

    return Role::create($validatedData);
  }

  public function deleteRole(RoleType $role)
  {
    $roleModel = Role::where('name', $role->value)->firstOrFail();
    return $roleModel->delete();
  }

  //TODO: Need Fixing this function 
  public function getUserStats(): array
  {
    $totalUsers = User::count();
    $activeUsers = User::where('is_active', true)->count();
    $usersByRole = DB::table('model_has_roles')
      ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
      ->select('roles.name', DB::raw('count(*) as count'))
      ->groupBy('roles.name')
      ->get();

    return [
      'total_users' => $totalUsers,
      'active_users' => $activeUsers,
      'inactive_users' => $totalUsers - $activeUsers,
      'users_by_role' => $usersByRole
    ];
  }
}

