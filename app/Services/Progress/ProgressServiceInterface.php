<?php

namespace App\Services\Progress;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

interface ProgressServiceInterface
{
    public function markLessonAsCompleted(User $user, Course $course, Lesson $lesson);
    public function markLessonAsIncomplete(User $user, Course $course, Lesson $lesson);
    public function getCourseProgress(User $user, Course $course);
}
