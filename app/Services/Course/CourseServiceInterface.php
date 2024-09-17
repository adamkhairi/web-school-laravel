<?php

namespace App\Services\Course;

use App\Models\Course;
use Illuminate\Http\Request;

interface CourseServiceInterface
{
    public function getCourses(Request $request);
    public function getCourse(Course $course);
    public function createCourse(array $data);
    public function updateCourse(Course $course, array $data);
    public function deleteCourse(Course $course);
}
