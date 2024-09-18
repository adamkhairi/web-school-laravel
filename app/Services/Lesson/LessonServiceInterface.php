<?php

namespace App\Services\Lesson;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;

interface LessonServiceInterface
{
    public function getLessons(Request $request, Course $course);
    public function createLesson(Course $course, array $data);
    public function updateLesson(Lesson $lesson, array $data);
    public function deleteLesson(Lesson $lesson);
    public function markLessonAsCompleted(User $user, Course $course, Lesson $lesson);
    public function markLessonAsIncomplete(User $user, Course $course, Lesson $lesson);
}
