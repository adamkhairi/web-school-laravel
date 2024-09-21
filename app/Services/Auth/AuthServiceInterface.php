<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface AuthServiceInterface
{
  public function login(Request $request): array;
  public function register(Request $request): array;
  public function logout(): void;
  public function getUserData(Request $request);
  public function updateProfile(Request $request): User|null;
  public function sendPasswordResetEmail(Request $request): void;
  public function resetPassword(Request $request): void;
  public function enableTwoFactorAuth(): array;
  public function disableTwoFactorAuth(): void;
  public function verifyTwoFactorAuth(Request $request): void;
  public function refresh(Request $request): array;
  public function verifyEmail(Request $request): void;
  public function assignRole(Request $request, User $user): void;
  public function removeRole(Request $request, User $user): void;
}
