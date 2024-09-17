<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Services\Enrollment\EnrollmentServiceInterface;

class EnrollmentController extends Controller
{
    
    /* 
    - Students can enroll in courses.
    - Enrollment requests are initially set to 'pending' status.
    - Administrators or teachers can approve or reject enrollment requests.
    - Students can view their enrollments.
    - Teachers can view enrollments for their courses.
    - Students can withdraw from courses.
    - The system checks for course capacity before allowing new enrollments.
    - Enrollment statistics are available for each course. 
    */

    protected $enrollmentService;

    public function __construct(EnrollmentServiceInterface $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    public function enroll(Request $request): JsonResponse
    {
        if (Gate::denies('enroll', Enrollment::class)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->enrollmentService->enroll($request);
            return response()->json($result, $result['status']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to submit enrollment request: ' . $e->getMessage()], 500);
        }
    }

    public function updateEnrollmentStatus(Request $request, Enrollment $enrollment): JsonResponse
    {
        if (Gate::denies('updateStatus', $enrollment)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->enrollmentService->updateEnrollmentStatus($request, $enrollment);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update enrollment status: ' . $e->getMessage()], 500);
        }
    }

    /* TODO Add pagination, sorting, filtering and search */
    public function getStudentEnrollments(): JsonResponse
    {
        if (Gate::denies('view', Enrollment::class)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        try {
            $enrollments = $this->enrollmentService->getStudentEnrollments();
            return response()->json($enrollments);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch enrollments: ' . $e->getMessage()], 500);
        }
    }

    /* TODO Add pagination, sorting, filtering and search */
    public function getCourseEnrollments(Course $course): JsonResponse
    {
        if (Gate::denies('view', [Enrollment::class, $course])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $enrollments = $this->enrollmentService->getCourseEnrollments($course);
            return response()->json($enrollments);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch course enrollments: ' . $e->getMessage()], 500);
        }
    }

    public function withdrawEnrollment(Enrollment $enrollment): JsonResponse
    {
        if (Gate::denies('withdraw', $enrollment)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        try {
            $result = $this->enrollmentService->withdrawEnrollment($enrollment);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to withdraw enrollment: ' . $e->getMessage()], 500);
        }
    }

    public function getEnrollmentStatistics(Course $course): JsonResponse
    {
        try {
            $stats = $this->enrollmentService->getEnrollmentStatistics($course);
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch enrollment statistics: ' . $e->getMessage()], 500);
        }
    }
}
