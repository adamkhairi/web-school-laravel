<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return $user->hasRole(RoleType::Teacher);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->hasRole(RoleType::Teacher) && $user->id === $assignment->course->teacher_id;
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->hasRole(RoleType::Teacher) && $user->id === $assignment->course->teacher_id;
    }

    public function submit(User $user, Assignment $assignment): bool
    {
        return $user->hasRole(RoleType::Student) && $assignment->course->students->contains($user);
    }

    public function viewSubmissions(User $user, Assignment $assignment): bool
    {
        return $user->hasRole(RoleType::Teacher) && $user->id === $assignment->course->teacher_id;
    }
}
