<?php

namespace App\Services\Enrollment;

use App\Models\Enrollment;
use App\Models\Course;
use Illuminate\Http\Request;

interface EnrollmentServiceInterface
{
    public function enroll(Request $request);
    public function getWaitlistedStudents(Course $course);
    public function updateEnrollmentStatus(Request $request, Enrollment $enrollment);
    public function getStudentEnrollments();
    public function getCourseEnrollments(Course $course);
    public function withdrawEnrollment(Enrollment $enrollment);
    public function getEnrollmentStatistics(Course $course);
    public function enrollInCourse(Course $course);
}
