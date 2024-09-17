<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\Role;
use App\Enums\RoleType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;

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

  public function assignRole(User $user, RoleType $role)
  {
    if (!$user->hasRole($role)) {
      $user->assignRole($role);
      return true;
    }
    return false;
  }

  public function removeRole(User $user, RoleType $role)
  {
    $roleModel = Role::where('name', $role->value)->firstOrFail();
    return $user->roles()->detach($roleModel->id);
  }

  public function getUserActivity(User $user)
  {
    return $user->activities()->latest()->take(50)->get();
  }

  public function bulkDeleteUsers($userIds)
  {
    return User::whereIn('id', $userIds)->delete();
  }

  public function exportUsers()
  {
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

    return new StreamedResponse($callback, 200, $headers);
  }

  public function getUserStats()
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
      'users_by_role' => $usersByRole
    ];
  }
}
