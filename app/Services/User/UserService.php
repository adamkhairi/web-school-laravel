<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\Role;
use App\Enums\RoleType;
use App\Exceptions\ApiException;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserService implements UserServiceInterface
{

  protected $userRepo;

  function __construct(UserRepositoryInterface $userRepo)
  {
    $this->userRepo = $userRepo;
  }

  public function getUsers($request)
  {
    return $this->userRepo->getAllUsers($request);
    // Search by name, email, or phone number

  }

  public function createUser($data)
  {
    $data['password'] = Hash::make($data['password']);
    return $this->userRepo->createUser($data);
  }

  public function updateUser(User $user, $data)
  {
    if (isset($data['password'])) {
      $data['password'] = Hash::make($data['password']);
    }

    return $this->userRepo->updateUser($user, $data);
  }

  public function deleteUser(User $user)
  {
    return $this->userRepo->deleteUser($user);
  }

  public function toggleUserActivation(User $user)
  {
    $this->userRepo->toggleUserActivation($user);
    return $user->is_active ? 'User activated' : 'User deactivated';
  }

  public function assignRole(User $user, RoleType $role): bool
  {
    if ($user->hasRole($role)) {
      return false;
    }

    return $this->userRepo->assignRole($user, $role);
  }

  public function removeRole(User $user, RoleType $role)
  {
    if (!$user->hasRole($role)) {
      throw new ApiException('User does not have this role', 400);
    }
    $this->userRepo->removeRole($user, $role);
  }

  //TODO: Fix and Implement user activity retrieval logic
  public function getUserActivity(User $user)
  {

    return $this->userRepo->getUserActivity($user);
    //return $user->activities()->latest()->take(50)->get();
  }

  public function bulkDeleteUsers($userIds)
  {
    $this->userRepo->bulkDeleteUsers($userIds);
  }

  public function exportUsers(): StreamedResponse
  {
    // Implement logic to export users

    return $this->userRepo->exportUsers();
  }


  public function addRole(array $data)
  {
    $validatedData = Validator::make($data, [
      'name' => 'required|string|unique:roles,name',
      'description' => 'nullable|string',
    ])->validate();

    return $this->userRepo->addRole($validatedData);
  }

  public function deleteRole(RoleType $role)
  {
    return $this->userRepo->deleteRole($role);
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

