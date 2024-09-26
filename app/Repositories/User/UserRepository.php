<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserRepository implements UserRepositoryInterface
{
  public function getAllUsers(array $filters): array
  {
    $query = User::query(); // Initialize the query builder

    // Search by name, email, or phone number
    if (isset($filters['search'])) {
      $searchTerm = $filters['search'];
      $query->where(function ($q) use ($searchTerm) {
        $q->where('name', 'like', "%$searchTerm%")
          ->orWhere('email', 'like', "%$searchTerm%")
          ->orWhere('first_name', 'like', "%$searchTerm%")
          ->orWhere('last_name', 'like', "%$searchTerm%")
          ->orWhere('phone_number', 'like', "%$searchTerm%");
      });
    }

    // Filter by role
    if (isset($filters['role'])) {
      $query->whereHas('roles', function ($q) use ($filters) {
        $q->where('name', $filters['role']);
      });
    }

    // Filter by active status
    if (isset($filters['active'])) {
      $query->where('is_active', $filters['active']);
    }

    // Filter by creation date
    if (isset($filters['created_after'])) {
      $query->where('created_at', '>=', $filters['created_after']);
    }
    if (isset($filters['created_before'])) {
      $query->where('created_at', '<=', $filters['created_before']);
    }

    // Sorting
    $sortField = $filters['sort_by'] ?? 'created_at';
    $sortDirection = $filters['sort_direction'] ?? 'desc';
    $query->orderBy($sortField, $sortDirection);

    // Pagination
    $perPage = $filters['per_page'] ?? 15;
    return $query->with('roles')->paginate($perPage);
  }

  public function findUserById(int $id): User
  {
    return User::findOrFail($id);
  }

  public function createUser(array $data): User
  {
    return User::create($data);
  }

  public function updateUser(User $user, array $data): User
  {
    $user->update($data);
    return $user;
  }

  public function deleteUser(User $user): void
  {
    $user->delete();
  }

  public function toggleUserActivation(User $user): void
  {
    // Implement logic to toggle user activation
    $user->is_active = !$user->is_active;
    $user->save();
  }

  public function assignRole(User $user, $role): bool
  {
    // Implement logic to assign role
    $user->assignRole($role);
    return true;
  }

  public function removeRole(User $user, $role): void
  {
    // Implement logic to remove role
    $user->removeRole($role);
  }

  public function getUserActivity(User $user): array
  {
    // Implement logic to get user activity

    // This is a placeholder and should be replaced with actual logic
    return [
      'last_login' => $user->last_login,
      'course_progress' => $user->courseProgress,
      'total_courses' => $user->totalCourses,
      'total_activities' => $user->totalActivities,
      'total_time' => $user->totalTime,
      // Add more activity data as needed
    ];
  }

  public function bulkDeleteUsers(array $userIds): void
  {
    /* return DB::transaction(function () use ($userIds) {
      return User::whereIn('id', $userIds)->delete();
    }); */
    User::destroy($userIds);
  }

  public function exportUsers(): StreamedResponse
  {
    // Implement logic to export users
    return response()->stream(function () {
      // Implement logic to export users
      $file = fopen('php://output', 'w');
      fputcsv($file, ['ID', 'Name', 'Email', 'Created At']);

      User::chunk(1000, function ($users) use ($file) {
        foreach ($users as $user) {
          fputcsv($file, [$user->id, $user->name, $user->email, $user->created_at]);
        }
      });

      fclose($file);

    }, 200, [
      "Content-type" => "text/csv",
      "Content-Disposition" => "attachment; filename=users.csv",
      "Pragma" => "no-cache",
      "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
      "Expires" => "0"
    ]);
  }

  public function addRole(array $data): Role
  {
    // Implement logic to add role    
    return Role::create($data);
  }

  public function deleteRole($role): void
  {
    // Implement logic to delete role
    Role::where('name', $role->value)->firstOrFail()->delete();
  }

  public function getUserStats(): array
  {
    // Implement logic to get user stats
  }
}
