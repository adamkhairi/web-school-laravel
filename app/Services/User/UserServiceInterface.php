<?php

namespace App\Services\User;

use App\Models\User;
use App\Enums\RoleType;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface UserServiceInterface
{
    public function getUsers(Request $request);
    public function createUser(array $data);
    public function updateUser(User $user, array $data);
    public function deleteUser(User $user);
    public function toggleUserActivation(User $user);
    public function assignRole(User $user, RoleType $role);
    public function removeRole(User $user, RoleType $role);
    public function getUserActivity(User $user);
    public function bulkDeleteUsers(array $userIds);
    public function exportUsers(): StreamedResponse;
    public function getUserStats(): array;
    public function addRole(array $data);
    public function deleteRole(RoleType $role);
}
