<?php

namespace App\Repositories\Auth;

use App\Models\User;
use Illuminate\Http\Request;

interface AuthRepositoryInterface
{
    public function createUser(array $data): User;
    public function findUserByEmail(string $email): ?User;
    public function updateUser(User $user, array $data): User;
    public function deleteUser(User $user): void;
}
