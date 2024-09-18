<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubmissionPolicy
{
    public function grade(User $user, Submission $submission): bool
    {
        return $user->hasRole(RoleType::Teacher) && $user->id === $submission->assignment->course->teacher_id;
    }
}
