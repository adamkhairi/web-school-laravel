<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface AuthServiceInterface
{
  public function login(Request $request): JsonResponse;
  public function register(Request $request): JsonResponse;
  public function logout(): JsonResponse;
  public function getUserData(): JsonResponse;
  public function updateProfile(Request $request): JsonResponse;
  public function sendPasswordResetEmail(Request $request): JsonResponse;
  public function resetPassword(Request $request): JsonResponse;
  public function enableTwoFactorAuth(): JsonResponse;
  public function disableTwoFactorAuth(): JsonResponse;
  public function verifyTwoFactorAuth(Request $request): JsonResponse;
  public function verifyEmail(Request $request): JsonResponse;
  public function refresh(Request $request);
  public function assignRole(Request $request, User $user);
  public function removeRole(Request $request, User $user);
}
