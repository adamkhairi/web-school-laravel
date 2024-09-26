<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface UserRepositoryInterface
{
    public function getAllUsers(array $filters): array;
    public function findUserById(int $id): User;
    public function createUser(array $data): User;
    public function updateUser(User $user, array $data): User;
    public function deleteUser(User $user): void;
    public function toggleUserActivation(User $user): void;
    public function assignRole(User $user, $role): bool;
    public function removeRole(User $user, $role): void;
    public function getUserActivity(User $user): array;
    public function bulkDeleteUsers(array $userIds): void;
    public function exportUsers(): StreamedResponse;
    public function addRole(array $data): Role;
    public function deleteRole($role): void;
    public function getUserStats(): array;
}
