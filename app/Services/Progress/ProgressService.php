<?php

namespace App\Services\Progress;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Progress;
use App\Models\User;

class ProgressService implements ProgressServiceInterface
{
    public function markLessonAsCompleted(User $user, Course $course, Lesson $lesson)
    {
        return Progress::updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id, 'lesson_id' => $lesson->id],
            ['completed' => true]
        );
    }

    public function markLessonAsIncomplete(User $user, Course $course, Lesson $lesson)
    {
        return Progress::updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id, 'lesson_id' => $lesson->id],
            ['completed' => false]
        );
    }

    public function getCourseProgress(User $user, Course $course)
    {
        $totalLessons = $course->lessons()->count();
        $completedLessons = $course->lessons()
            ->whereHas('progress', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('completed', true);
            })
            ->count();

        return [
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'percentage' => $course->progressPercentage($user),
        ];
    }
}
