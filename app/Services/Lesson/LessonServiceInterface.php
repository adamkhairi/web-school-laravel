<?php

namespace App\Services\Lesson;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;

interface LessonServiceInterface
{
    public function getLessons(Request $request, Course $course);
    public function createLesson(Course $course, array $data);
    public function updateLesson(Lesson $lesson, array $data);
    public function deleteLesson(Lesson $lesson);
}
