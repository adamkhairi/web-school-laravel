<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Allow all authenticated users to view their enrollments
    }

    public function updateStatus(User $user, Enrollment $enrollment): bool
    {
        return $user->hasRole(RoleType::Admin) || $user->hasRole(RoleType::Teacher);
    }

    public function view(User $user): bool
    {
        return true; // Allow all authenticated users to view their enrollments
    }

    public function enroll(User $user): bool
    {
        return $user->hasRole(RoleType::Student);
    }

    public function withdraw(User $user, Enrollment $enrollment): bool
    {
        return $user->id === $enrollment->user_id;
    }
}
