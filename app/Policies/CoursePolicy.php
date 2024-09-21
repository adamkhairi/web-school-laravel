<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CoursePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleType::Admin) || $user->hasRole(RoleType::Teacher);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Course $course): bool
    {
        return $user->hasRole(RoleType::Admin) || $user->hasRole(RoleType::Teacher) || $course->students->contains($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(RoleType::Admin) || $user->hasRole(RoleType::Teacher);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->hasRole(RoleType::Admin) || ($user->hasRole(RoleType::Teacher) && $user->id === $course->teacher_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->hasRole(RoleType::Admin) || ($user->hasRole(RoleType::Teacher) && $user->id === $course->teacher_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Course $course): bool
    {
        return $user->hasRole(RoleType::Admin) || ($user->hasRole(RoleType::Teacher) && $user->id === $course->teacher_id);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Course $course): bool
    {
        //
        return $user->hasRole(RoleType::Admin) || ($user->hasRole(RoleType::Teacher) && $user->id === $course->teacher_id);
    }

    /**
     * Determine whether the user can enroll in the model.
     */
    public function enroll(User $user, Course $course): bool
    {
        return $user->hasRole(RoleType::Student) || $user->hasRole(RoleType::Admin);
    }

}
